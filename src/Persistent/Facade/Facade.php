<?php

namespace Flake\Persistent\Facade;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;

interface Facade
{
    public function getBuilder(): AbstractBuilder;
    public function getDriver(): AbstractDriver;
}
