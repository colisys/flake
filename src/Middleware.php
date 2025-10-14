<?php
namespace Flake;

class Middleware
{
    /**
     * @var array<\Closure(Request $request, Response $response, Middleware $next)>
     */
    protected static array $middlewares = [];

    /**
     * Add a middleware to the stack
     *
     * @param \Closure(Request $request, Response $response, Middleware $next) $middleware
     */
    public static function use (callable $middleware): void
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
        // When middleware returns not as [$request, $response], the following code will raise an error
        // // Build the middleware chain from last to first
        // $next = fn($req, $res) => [$req, $res];

        // foreach (array_reverse(self::$middlewares) as $middleware) {
        //     $next = fn($req, $res) => $middleware($req, $res, $next);
        // }

        // // Run the fully composed chain
        // return $next($request, $response);

        $tguard = function (Request $request, Response $response) {
            return function () use ($request, $response) {
                return [$request, $response];
            };
        };

        $count = 0;
        foreach (array_reverse(self::$middlewares) as $middleware) {
            $count++;
            $tguard = $middleware($request, $response, $tguard);
            // When middleware returns null, stop the middleware stack
            if ($tguard === null) {
                // TODO: should we dispatch an event?
                // Events::dispatch('App.Error', new \Exception("Middleware stopped at no.$count middleware."));
                die();
            }
        }

        return [$request, $response];
    }
}
