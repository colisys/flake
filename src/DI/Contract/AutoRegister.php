<?php

namespace Flake\DI\Contract;

use Psr\Container\ContainerInterface;

interface AutoRegister
{
    public static function onAutoRegiste(ContainerInterface $container): void;

}
