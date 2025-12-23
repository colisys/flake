<?php

namespace Flake\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Rule
{
    /**
     * @param int $weight
     * @param "pre"|"post" $execution
     */
    public function __construct(
        public int $weight = 0,
        public string $execution = "pre"
    ) {}
}
