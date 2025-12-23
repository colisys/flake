<?php

namespace Flake\View;

use Exception;
use Flake\Attributes\Component;
use Flake\Cache\Facade\AbstractFacade;
use Flake\DI\ComponentCollector;
use Flake\DI\Contract\AutoRegisterClass;
use Flake\Response;
use Flake\View\Attribute\Rule;
use Flake\View\Rules\AbstractRule;
use Flake\View\Rules\XSSClean;
use Psr\Container\ContainerInterface;

use function Flake\config;
use function Flake\dd;
use function Flake\make;

#[Component()]
class Renderer extends AutoRegisterClass
{
    protected string $basePath = '';
    protected string $extension = 'html';
    protected string $view = '';
    protected bool $enable_cache = false;
    protected string $cache_path = '';
    protected array $data = [];
    protected int $cache_ttl = 0;
    protected array $allow_rules = [];
    private static array $preRules = [];
    private static array $postRules = [];

    public function __construct(
        protected ContainerInterface $container
    ) {
        $config = config('view', []);
        $this->basePath = $config['path'] ?? (BASE_DIR . '/views');
        $this->extension = $config['extension'] ?? 'html';
        $this->enable_cache = (isset($config['enable_cache']) && $config['enable_cache'] == 1) ?? false;
        $this->cache_path = $config['cache_path'] ?? sys_get_temp_dir();
        $this->cache_ttl = $config['cache_ttl'] ?? -1;
        $this->allow_rules = $config['rules'] ?? [];

        if (!count(self::$preRules) || !count(self::$postRules)) {
            $classes =  ComponentCollector::getClassesByAttribute(Rule::class);

            foreach ($classes as $key => $value) {
                if (!in_array($key, $this->allow_rules)) {
                    unset($classes[$key]);
                    continue;
                }
                $args = $value->getAttributes(Rule::class)[0]->getArguments();
                if (isset($args['execution']) && $args['execution'] == 'post') {
                    self::$postRules[] = $value;
                } else {
                    self::$preRules[] = $value;
                }
            }

            $sort = function ($a, $b) {
                $argsA = $a->getAttributes(Rule::class)[0]->getArguments();
                $argsB = $b->getAttributes(Rule::class)[0]->getArguments();

                $weightA = $argsA['weight'] ?? 0;
                $weightB = $argsB['weight'] ?? 0;

                return $weightA <=> $weightB;
            };

            usort(self::$preRules, $sort);
            usort(self::$postRules, $sort);
        }
    }

    /**
     * Set the view to be rendered.
     * 
     * @param string $view Path relative to project root (without .php)
     * @param array|object $data Data to be extracted into view
     */
    public function view(string $view, array | object $data = []): static
    {
        $this->view = $view;
        $this->data = $data;
        return $this;
    }

    /**
     * Get the data passed to the view.
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function clear(): static
    {
        $this->view = '';
        $this->data = [];
        ob_end_clean();
        return $this;
    }

    /**
     * Render a view file.
     */
    public function render(bool $returns = false, bool $noxss = false)
    {
        $cache = make(AbstractFacade::class);

        $response = make(Response::class);
        $viewPath = $this->basePath . '/' . ltrim($this->view, '/') . ".{$this->extension}";

        if ($this->enable_cache && $cache->has($viewPath)) {
            $template = $cache->get($viewPath);
            $info = $cache->getInfo($viewPath);
            [$_, $template] = explode(PHP_EOL, $template, 2);
            $template = "<?php extract(unserialize('" . serialize($this->data) . "')); ?>\n" . $template;
            $cache->set($viewPath, $template, ($info['expires'] - time()));

            return $response->stream()
                ->status(200)
                ->header('Content-Type', 'text/html; charset=utf-8')
                ->send($this->hit($template))
                ->end();
        }

        if (! file_exists($viewPath)) {
            throw new Exception("View not found: {$viewPath}");
            return;
        }

        // Create a new environment for the view
        $output = $this->execRules(
            self::$postRules,
            $this->execRules(
                self::$preRules,
                file_get_contents($viewPath),
                $noxss,
            ),
            $noxss,
        );

        if (!$returns) {
            try {
                $template = "<?php extract(unserialize('" . serialize($this->data) . "')); ?>\n" . $output;
                $output = $this->hit($template);
                if ($this->enable_cache)
                    $cache->set($viewPath, $template, $this->cache_ttl);

                // Send the output to the client
                return $response->stream()
                    ->header('Content-Type', 'text/html; charset=utf-8')
                    ->status(200)
                    ->send($output)
                    ->end();
            } catch (\Exception $e) {
                dd($e);
            }
        } else return $output;
    }

    /**
     * Execute a rule set
     * 
     * @param array<\ReflectionClass> $rules
     * @param string $contents
     * @param bool $noxss
     */
    protected function execRules(array $rules, string $contents, bool $noxss = false)
    {
        foreach ($rules as $rule) {
            if ($noxss && $rule->getName() == XSSClean::class) {
                $contents = "<noxss>" . $contents . "</noxss>";
                continue;
            }

            $rule = make($rule->getName());
            if ($rule instanceof AbstractRule) {
                if ($rule::$fullContext) {
                    if ($rule::test($contents))
                        $contents = $rule::apply($contents, $this);
                } else {
                    $c = explode(PHP_EOL, $contents);
                    $d = array_filter($c, fn($l) => $rule::test($l));
                    foreach ($d as $index => $line) {
                        $md = $rule::apply([$index => $line], $this);
                        $c[$index] = $md[$index];
                    }
                    $contents = implode(PHP_EOL, $c);
                }
            }
        }
        return $contents;
    }

    public function hit(...$data)
    {
        // Create a temporary file
        $fd = tmpfile();
        foreach ($data as $d)
            fwrite($fd, $d);
        $filename = stream_get_meta_data($fd)['uri'];

        // Include the file, and capture the output, this will execute the view
        ob_start();
        include $filename;
        $output = ob_get_clean();
        ob_end_clean();
        fclose($fd);
        return $output;
    }
}

if (!function_exists('view')) {
    function view(string $view, array | object $data = [])
    {
        return make(\Flake\View\Renderer::class)->view($view, $data);
    }
}

if (!function_exists('render')) {
    function render(string $view, array | object $data = [])
    {
        return make(\Flake\View\Renderer::class)
            ->clear()
            ->view($view, $data)
            ->render(true);
    }
}
