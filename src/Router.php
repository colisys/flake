<?php

namespace Flake;

class Router
{
    protected static array $routes = [];
    protected static $fallback = null;

    static function get($uri, $callback)
    {
        self::$routes['GET'][$uri] = $callback;
    }

    public static function routes(): array
    {
        return self::$routes;
    }

    public static function fallback(callable $callback): void
    {
        self::$fallback = $callback;
    }
}
