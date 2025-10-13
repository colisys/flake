<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Flake\App;
use Flake\Router;

Router::get('/', function ($req, $res) {
    $res->send('Hello, world!');
});

App::run();
