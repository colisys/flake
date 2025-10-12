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
    public static function run(): array
    {
        $request  = new Request();
        $response = new Response();
        $request->setParams($_REQUEST);

        $tguard = function (Request $request, Response $response) {
            return function () use ($request, $response) {
                return [$request, $response];
            };
        };

        try {
            $count = 0;
            foreach (self::$middlewares as $middleware) {
                $count++;
                $tguard = $middleware($request, $response, $tguard);
                // When middleware returns null, stop the middleware stack
                if ($tguard === null) {
                    die();
                }
            }

            return [$request, $response];
        } catch (\Throwable $th) {
            // Error message to console
            error_log("Error captured in middleware stack, code: " . $th->getCode() . ', message: ' . $th->getMessage() . PHP_EOL .
                '== Trace Begin == ' . PHP_EOL . PHP_EOL . $th->getTraceAsString() . PHP_EOL . PHP_EOL . '== Trace End ==');
            $response
                ->status(500)
                ->send("Internal Server Error");
            die();
        }
    }
}
