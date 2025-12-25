<?php

namespace Flake\Persistent\Event;

class DatabaseQueryEvent
{
    public function __construct(public string $lastSql) {}
}
