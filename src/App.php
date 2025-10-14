<?php
namespace Flake;

use Flake\Exceptions\RouterNotFoundException;

class App
{
    public static function run()
    {
        // Ensure initialized (lazy-init pattern)
        if (! self::$basePath) {
            self::init(getcwd());
        }

        $request  = new Request();
        $response = new Response();
        // TODO: should we sanitize the request?
        $request->setParams($_REQUEST);

        try {
            $router = Router::buildRouter($request, $response);
            Middleware::run($request, $response);
            Router::dispatch($request, $response, $router);
        } catch (\Throwable $th) {
            if ($th instanceof RouterNotFoundException || $th instanceof RouterNotFoundException) {
                // No route found, try middleware?
                Middleware::run($request, $response);
            }
        }
    }

    protected static string $basePath = '';

    public static function init(string $basePath = __DIR__): static
    {
        self::$basePath = rtrim($basePath, '/');
        return new static();
    }

    public static function path(?string $path = null): string
    {
        if ($path) {
            self::$basePath = rtrim($path, '/');
        }

        return self::$basePath;
    }
}
