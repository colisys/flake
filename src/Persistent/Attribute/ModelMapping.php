<?php

namespace Flake\Persistent\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ModelMapping
{
    public function __construct(
        public string $pk,
        public string|int $defaults = 0
    ) {}
}
