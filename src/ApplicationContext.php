<?php

namespace Flake;

use Psr\Container\ContainerInterface;


class ApplicationContext
{
    private static ContainerInterface $container;

    public function __construct(?ContainerInterface $container)
    {
        self::$container = $container ?? new Container();
    }

    public static function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
