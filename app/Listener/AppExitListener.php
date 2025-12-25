<?php

namespace App\Listener;

use Flake\Event\Attribute\EventHandler;
use Flake\Event\Attribute\EventListener;
use Flake\Event\Builtin\AppExitEvent;

use function Flake\dd;

#[EventListener]
class AppExitListener
{
    #[EventHandler(AppExitEvent::class)]
    public function handle(object $event)
    {
        dd($event);
    }
}
