<?php

namespace Flake\Persistent\Event;

use Flake\Persistent\Model;

class AfterInsert
{
    public function __construct(public Model $model) {}
}
