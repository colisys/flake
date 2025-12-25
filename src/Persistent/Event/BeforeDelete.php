<?php

namespace Flake\Persistent\Event;

use Flake\Event\Contract\AbstractStoppableEvent;
use Flake\Persistent\Model;

class BeforeDelete extends AbstractStoppableEvent
{
    public function __construct(public Model $model) {}
}
