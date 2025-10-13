<?php

namespace Flake;

class App
{
    public static function run()
    {
        // Ensure initialized (lazy-init pattern)
        if (!self::$basePath) {
            self::init(getcwd());
        }
        [$request, $response] = Middleware::run();
        Router::dispatch($request, $response);
    }

    protected static string $basePath = '';

    public static function init(string $basePath = __DIR__): static
    {
        self::$basePath = rtrim($basePath, '/');
        return new static();
    }

    public static function path(?string $path = null): string
    {
        if ($path) self::$basePath = rtrim($path, '/');
        return self::$basePath;
    }
}
