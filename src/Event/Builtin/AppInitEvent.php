<?php

namespace Flake\Event\Builtin;

class AppInitEvent
{
    public function __construct(
        public string $instanceId,
    ) {}
}
