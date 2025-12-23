<?php

namespace Flake\Persistent\Facade;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Builder\SqliteBuilder;
use Flake\Persistent\Driver\AbstractDriver;
use Flake\Persistent\Driver\SqliteDriver;
use Psr\Container\ContainerInterface;

class SqliteFacade implements AbstractFacade
{
    public function __construct(
        protected ContainerInterface $container,
        protected SqliteBuilder $builder,
        protected SqliteDriver $driver
    ) {
        if (method_exists($container, "set")) {
            $container->{"set"}(AbstractBuilder::class, $builder);
            $container->{"set"}(AbstractDriver::class, $driver);
        }
    }

    public function getBuilder(): AbstractBuilder
    {
        return $this->builder;
    }

    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }
}
