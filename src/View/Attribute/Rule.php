<?php

namespace Flake\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Rule
{
    public function __construct() {}
}
