<?php

namespace Flake\DI;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private static $instances = [];

    public function get(string $id)
    {
        if (!isset(self::$instances[$id])) {
            return null;
        } elseif ($info = ComponentCollector::getClassInfo($id)) {
            if (!$info['t'])
                return clone self::$instances[$id];
        }

        return self::$instances[$id];
    }

    public function has(string $id): bool
    {
        return isset(self::$instances[$id]);
    }

    public function set(string $id, $instance)
    {
        self::$instances[$id] = $instance;
    }
}
