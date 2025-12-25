<?php

namespace App\Listener;

use Flake\Event\Attribute\EventHandler;
use Flake\Event\Attribute\EventListener;
use Flake\Event\Contract\AbstractListener;
use Flake\Persistent\Event\DatabaseQueryEvent;

use function Flake\dd;

#[EventListener]
class DatabaseQueryListener extends AbstractListener
{
    public array $events = [DatabaseQueryEvent::class];

    public function handle(object $event)
    {
        if ($event instanceof DatabaseQueryEvent)
            error_log('Database query executed: ' . $event->lastSql);
    }
}
