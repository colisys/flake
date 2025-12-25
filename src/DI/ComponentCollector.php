<?php

namespace Flake\DI;

use Flake\DI\Attributes\Component;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;

use function Flake\config;
use function Flake\dd;

class ComponentCollector
{
    /**
     * @var array<string,array{"s":string,"a":string[],"p":array{name:string,type:string,default:mixed},"m":string[],"c":bool,"l":bool,"d":bool,"i":string[],"t":bool}>
     */
    protected static array $components = [];

    public function __construct(
        ContainerInterface $container,
    ) {
        self::$components = static::collect($container);
        foreach (self::getAutoRegisterClassesMethods() as  $class => $autoRegisterMethod) {
            $reflectionClass = new \ReflectionClass($class);
            if ($attrs = $reflectionClass->getAttributes(Component::class)) {
                /**
                 * @var Component $component
                 */
                $component = $attrs[0]->newInstance();
                if ($component->auto_register) {
                    $autoRegisterMethod->invoke(null, $container);

                    if ($component->alias !== null)
                        $container->{"set"}($component->alias, $container->get($reflectionClass->getName()));
                }
            }
        }

        foreach (self::getAfterAutoRegisterClassesMethods() as $class => $autoRegisterMethod) {
            $class = $container->get($class);
            $class?->{"onAfterAutoRegiste"}($container);
        }
    }

    /**
     * Get method by name
     * 
     * @param string $class
     * @param string $methodName
     * @return \ReflectionMethod|null
     */
    public static function getMethodByName(string $class, string $methodName): \ReflectionMethod|null
    {
        if (class_exists($class) && array_key_exists($class, static::$components)) {
            $methods = static::$components[$class]['m'] ?? null;
            if (in_array($methodName, $methods)) {
                return new \ReflectionMethod($class, $methodName);
            }
        }
        return null;
    }

    /**
     * Get classes by attribute name
     *
     * @param string $attributeName
     * @return array<\ReflectionClass>
     */
    public static function getClassesByAttribute(string $attributeName): array
    {
        return array_map(
            fn($info) => new ReflectionClass($info['s']),
            array_filter(
                static::$components,
                fn($info) => in_array($attributeName, $info['a'] ?? [])
            ),
        );
    }

    /**
     * Get classes that implements an interface
     * 
     * @param string $interfaceName
     */
    public static function getClassesByInterface(string $interfaceName): array
    {
        return array_map(
            fn($info) => new ReflectionClass($info['s']),
            array_filter(
                static::$components,
                fn($info) => in_array($interfaceName, $info['i'] ?? [])
            ),
        );
    }

    /**
     * Get class info
     * 
     * @param string $className
     * @return ?array{"s":string,"a":string[],"p":array{name:string,type:string,default:mixed},"m":string[],"c":bool,"l":bool,"d":bool,"i":string[],"t":bool}
     */
    public static function getClassInfo(string $className): ?array
    {
        return static::$components[strtolower($className)] ?? null;
    }

    /**
     * Get auto register classes methods
     * 
     * @return array<ReflectionMethod>
     */
    protected static function getAutoRegisterClassesMethods(): array
    {
        return array_map(
            fn($info) => new ReflectionMethod($info['s'], 'onAutoRegiste'),
            array_filter(
                static::$components,
                fn($info) => $info['l'] ?? false
            ),
        );
    }

    /** 
     * Get after auto register classes methods
     * 
     * @return array<ReflectionMethod>
     */
    protected static function getAfterAutoRegisterClassesMethods(): array
    {
        return array_map(
            fn($info) => new ReflectionMethod($info['s'], 'onAfterAutoRegiste'),
            array_filter(
                static::$components,
                fn($info) => $info['d'] ?? false
            ),
        );
    }

    protected static function collect(ContainerInterface $container): array
    {
        /**
         * @var array<int|string,\Generator<array-key,\SplFileInfo>> $class_map
         */
        $class_map  = [];
        array_filter(
            require_once BASE_DIR . '/vendor/composer/autoload_psr4.php',
            function ($path, $namespace) use (&$class_map) {
                foreach (array_merge(config('dependencies.scan.namespaces', []), ['Flake\\']) as $scanNs) {
                    if (str_starts_with($namespace, $scanNs)) {
                        $p = array_shift($path);
                        $class_map[$namespace] = rscandir($p, '/\.php$/');
                    }
                }
            },
            ARRAY_FILTER_USE_BOTH
        );

        $result = [];
        foreach ($class_map as $files) {
            while ($files->valid()) {
                $file = $files->current();

                $contents = file_get_contents($file->getPathname());
                preg_match('/^namespace\s+(.+?);/m', $contents, $matches);
                if (isset($matches[1])) {
                    $ns = $matches[1];

                    preg_match('/^class\s+([^\ \{\n]+)/m', $contents, $matches);
                    if (isset($matches[1])) {
                        $class = $matches[1];
                        $result["$ns\\$class"] = $info = ComponentVisitor::visit("\\$ns\\$class");
                    }
                }

                $files->next();
            }
        }
        return $result;
    }
}

if (!function_exists('rscandir')) {
    /**
     * Recursive scan directory
     * 
     * @param string $dir
     * @param string $pattern
     * @return \Generator<array-key,\SplFileInfo>
     */
    function rscandir(string $dir, $pattern)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /**
         * @var \SplFileInfo $file
         */
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match($pattern, $file->getFilename()) > 0) {
                yield $file;
            }
        }
    }
}
