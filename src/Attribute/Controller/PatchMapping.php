<?php

namespace Flake\Attribute\Controller;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PatchMapping
{
    public function __construct(
        public string $path = ''
    ) {}
}
