<?php

namespace Flake\Persistent\Event;

use Flake\Persistent\Model;

class AfterSave
{
    public function __construct(public Model $model) {}
}
