<?php

namespace Flake\Event\Builtin;

class MiddlewareExecutionFailedEvent
{
    public function __construct(callable $middlware) {}
}
