<?php

namespace Flake\Attribute\Controller;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
    public function __construct(
        public string $prefix = '',
    ) {}
}
