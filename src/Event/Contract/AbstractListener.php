<?php

namespace Flake\Event\Contract;

abstract class AbstractListener
{
    public array $events = [];

    /**
     * @param Event $event
     */
    abstract public function handle(object $event);
}
