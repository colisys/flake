<?php

namespace Flake\Event\Contract;

interface AbstractDispatcher extends \Psr\EventDispatcher\EventDispatcherInterface, \Psr\EventDispatcher\ListenerProviderInterface
{
    public function addEventListener($event, callable $listener, $priority = 0): static;
    public function removeEventListener($event, callable $listener): static;
}
