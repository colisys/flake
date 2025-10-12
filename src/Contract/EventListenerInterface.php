<?php

namespace Flake\Contract;

interface EventListenerInterface
{
    public static function handle(...$args): void;
}