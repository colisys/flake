<?php

namespace Flake\Event\Builtin;

class AppExitEvent
{
    public function __construct(
        public string $instanceId,
    ) {}
}
