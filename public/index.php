<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Flake\App;
use Flake\Events;

require __DIR__ . '/../routes/examples.php';
require __DIR__ . '/../middlewares/examples.php';

Events::listen('App.Start', static fn() => error_log('App started'));
Events::listen('Router.NotFound', static fn(Throwable $th) => error_log('Route not found: ' . $th->getMessage()));

App::run();
