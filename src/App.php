<?php


namespace Flake;


use Flake\DI\ApplicationContext;
use Flake\Exceptions\NotSupportHTTPMethodException;

class App
{
    public string $instanceId = '';
    protected ApplicationContext $context;

    public function __construct()
    {
        require_once __DIR__ . '/Helper.php';
        $this->instanceId = uniqid();
        self::path(getenv("BASE_DIR") ?? defined('BASE_DIR') ? BASE_DIR : null);

        // Initialize ApplicationContext
        $this->context = ApplicationContext::init(self::path());

        if ($container = $this->context->getContainer()) {
            if (method_exists($container, 'set'))
                $container->{"set"}(App::class, $this);
        }
    }

    /**
     * Run the app
     *
     * @return void
     */
    public static function run()
    {
        // Ensure initialized (lazy-init pattern)
        if (! self::$basePath) {
            self::init(getcwd());
        }

        $request  = make(Request::class);
        $response = make(Response::class);
        // TODO: should we sanitize the request?
        $request->setParams($_REQUEST);

        try {
            $router = Router::buildRouter($request, $response);
            list($request, $response) = Middleware::run($request, $response);
            Router::dispatch($request, $response, $router);
        } catch (\Throwable $th) {
            if ($th instanceof NotSupportHTTPMethodException) {
                // Try to run middleware
                list($request, $response) = Middleware::run($request, $response);
                // Check middleware sent response or not
                if ($response->sent) {
                    return;
                }
            }

            dd($th);
            $response->status(500)->send('Internal Server Error');
        }
    }

    protected static string $basePath = '';

    /**
     * Initialize the app
     *
     * @param string $basePath
     * @return static
     */
    public static function init(string $basePath = __DIR__): static
    {
        self::$basePath = rtrim($basePath, '/');
        return new static();
    }

    /**
     * Get or set the base path
     *
     * @param string|null $path
     * @return string
     */
    public static function path(?string $path = null): string
    {
        if ($path) {
            self::$basePath = rtrim($path, '/');
        }

        return self::$basePath;
    }
}

if (!function_exists("make")) {
    /**
     * @template T
     * @param class-string<T> $class
     * @param array $options
     * @return T
     */
    function make($class, $options = [], $persist = true)
    {
        $class = str_replace("/", "\\", $class);
        $container = \Flake\DI\ApplicationContext::getContainer();
        if ($container->has($class)) {
            return $container->get($class);
        } else if (class_exists($class)) {
            ApplicationContext::make($class, $options, $persist);
            return $container->get($class);
        }
        return null;
    }
}
