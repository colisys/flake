<?php

namespace Flake\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Component
{
    public function __construct(
        public string|array|null $alias = null,
        public bool $auto_register = true,
        public bool $singleton = true,
    ) {}
}
