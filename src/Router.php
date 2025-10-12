<?php

namespace Flake;

class Router
{
    protected static array $routes = [];

    static function get($uri, $callback)
    {
        self::$routes['GET'][$uri] = $callback;
    }

    public static function routes(): array
    {
        return self::$routes;
    }
}
