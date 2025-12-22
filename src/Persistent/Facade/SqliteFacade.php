<?php

namespace Flake\Persistent\Facade;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Builder\SqliteBuilder;
use Flake\Persistent\Driver\AbstractDriver;
use Flake\Persistent\Driver\SqliteDriver;

class SqliteFacade implements AbstractFacade
{
    public function __construct(
        protected SqliteBuilder $builder,
        protected SqliteDriver $driver
    ) {}

    public function getBuilder(): AbstractBuilder
    {
        return $this->builder;
    }

    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }
}
