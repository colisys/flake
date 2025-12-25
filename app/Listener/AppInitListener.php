<?php

namespace App\Listener;

use Flake\Event\Attribute\EventHandler;
use Flake\Event\Attribute\EventListener;
use Flake\Event\Builtin\AppInitEvent;

use function Flake\dd;

#[EventListener]
class AppInitListener
{
    #[EventHandler(AppInitEvent::class)]
    public function handle(object $event) {
        dd($event);
    }
}
