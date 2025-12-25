<?php

namespace Flake\Event\Contract;

use Psr\EventDispatcher\StoppableEventInterface;

class AbstractStoppableEvent implements StoppableEventInterface
{

    private bool $isStopped = false;

    public function stopPropagation(): void
    {
        $this->isStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->isStopped;
    }
}
