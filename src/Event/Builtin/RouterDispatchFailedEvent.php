<?php

namespace Flake\Event\Builtin;

class RouterDispatchFailedEvent
{
    public function __construct(
        public \Throwable $exception,
    ) {}
}
