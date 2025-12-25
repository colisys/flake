<?php

namespace Flake\Event\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class EventHandler
{
    public function __construct(
        public string $event,
        public bool $enable = true
    ) {}
}
