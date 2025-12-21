<?php

namespace Flake\Persistent\Facade;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;

interface AbstractFacade
{
    public function getBuilder(): AbstractBuilder;
    public function getDriver(): AbstractDriver;
}
