<?php

namespace Flake\Attribute\Controller;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RestfulMapping
{
    public function __construct(
        public string $method,
        public string $path = '',
    ) {}
}
