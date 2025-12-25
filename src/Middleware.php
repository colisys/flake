<?php

namespace Flake;

use Psr\EventDispatcher\EventDispatcherInterface;

class Middleware
{
    /**
     * @var array<\Closure(Request $request, Response $response, \Closure $next)>
     */
    protected static array $middlewares = [];

    /**
     * Add a middleware to the stack
     *
     * @param \Closure(Request &$request, Response &$response, \Closure $next) $middleware
     */
    public static function use(callable $middleware): void
    {
        if (! is_callable($middleware)) {
            throw new \Exception("Middleware must be a callable.");
        }
        self::$middlewares[] = $middleware;
    }

    /**
     * Run the middleware stack
     *
     * @return array{0: Request, 1: Response}
     */
    public static function run(Request $request, Response $response): array
    {
        // Walk through the middleware stack, unless middleware returns null
        $middlewares = self::$middlewares;
        $result = function (Request $request, Response $response) {
            return function () use ($request, $response) {
                return [$request, $response];
            };
        };

        while (count($middlewares) > 0) {
            $middleware = array_shift($middlewares);
            $result = $middleware($request, $response, $result);
            if ($result == null) {
                make(EventDispatcherInterface::class)?->dispatch($middleware);
                die();
            }
            list($request, $response) = is_callable($result) ? $result() : $result;
        }

        return [$request, $response];
    }
}
