<?php
namespace Flake;

class App
{
    public static function run()
    {
        [$request, $response] = Middleware::run();
        Router::dispatch($request, $response);
    }
}
