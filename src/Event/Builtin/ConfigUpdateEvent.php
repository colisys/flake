<?php

namespace Flake\Event\Builtin;

class ConfigUpdateEvent
{
    public function __construct(string $key, $value) {}
}
