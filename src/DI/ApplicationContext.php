<?php

namespace Flake\DI;

use Flake\Config;
use Flake\DI\Exception\MissingDependencyException;
use Flake\DI\Exception\NotRegistedException;
use Flake\Env;
use Flake\Event\Dispatcher;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;

use function Flake\make;

class ApplicationContext
{
    private static ContainerInterface $container;

    public function __construct(?ContainerInterface $container = null)
    {
        self::$container = $container ?? new Container();
        if (method_exists(self::$container, 'set'))
            self::$container->{"set"}(ContainerInterface::class, self::$container);
    }

    public static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param array $options
     * @param bool $persist
     * @return T
     */
    public static function make(string $class, array $options = [], bool $persist = true)
    {
        if (self::getContainer()->has($class)) {
            return self::getContainer()->get($class);
        }

        $rclass = new ReflectionClass($class);

        // No user defined constructor
        if (!$rclass->getConstructor()) {
            $instance = new $class();
            if ($persist && method_exists(self::$container, 'set'))
                self::$container->{"set"}($class, $instance);
            return $instance;
        }

        $dependencies = [];
        foreach ($rclass->getConstructor()->getParameters() as $param) {
            $type = $param->getType();
            $name = $param->getName();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if (self::getContainer()->has($typeName)) {
                    $dependencies[$name] = self::getContainer()->get($typeName);
                } else if (class_exists($typeName)) {
                    $dependencies[$name] = self::make($typeName);
                } else {
                    throw new NotRegistedException("Not registered dependency: {$typeName}");
                }
            } else if ($param->isDefaultValueAvailable()) {
                $dependencies[$name] = $param->getDefaultValue();
            } else {
                if (!array_key_exists($name, $options))
                    throw new MissingDependencyException("Missing dependency for parameter: {$param->getName()} in class {$class}");
            }
        }

        $options = array_merge($dependencies, $options);
        $instance = $rclass->newInstanceArgs($options);
        if ($persist && method_exists(self::$container, 'set'))
            self::$container->{"set"}($class, $instance);
        return $instance;
    }

    public static function init(string $basePath)
    {
        // Use default container
        $context = new self(null);
        // Load environment
        make(Env::class, ['basePath' => $basePath]);
        // Load config
        make(Config::class, ['basePath' => $basePath]);
        // Initialize component collector
        // This will scan all components and registe them
        make(ComponentCollector::class, []);

        return $context;
    }
}

if (!function_exists("any")) {
    function any(...$values)
    {
        $arr = array_filter($values, fn($value) => $value !== null);
        if (!count($arr)) return null;
        return array_shift($arr);
    }
}
