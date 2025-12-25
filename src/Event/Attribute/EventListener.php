<?php

namespace Flake\Event\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class EventListener
{
    public function __construct() {}
}
