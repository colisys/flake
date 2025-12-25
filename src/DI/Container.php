<?php

namespace Flake\DI;

use Flake\DI\Exception\ContainerException;
use Flake\DI\Exception\NotRegistedException;
use Psr\Container\ContainerInterface;

use function Flake\dd;

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

        if (!$this->has($id)) {
            throw new NotRegistedException("Not registered dependency: {$id}");
        }

        try {
            return self::$instances[$id];
        } catch (\Throwable $th) {
            throw new ContainerException("Error when get dependency: {$id}", $th->getCode(), $th);
        }
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
