<?php

namespace Flake\DI\Contract;

use Psr\Container\ContainerInterface;

interface AutoRegister
{
    /**
     * @param ContainerInterface $container
     */
    public static function onAutoRegiste(ContainerInterface $container): void;
}
