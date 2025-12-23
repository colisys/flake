<?php

namespace Flake\DI\Contract;

use Psr\Container\ContainerInterface;

use function Flake\make;

abstract class AutoRegisterClass implements AutoRegister
{
    public static function onAutoRegiste(ContainerInterface $container): void
    {
        if (method_exists($container, 'set'))
            $container->{"set"}(static::class, make(static::class, persist: false));
    }
}
