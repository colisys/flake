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
        // Build the middleware chain from last to first
        $next = fn($req, $res) => [$req, $res];

        foreach (array_reverse(self::$middlewares) as $middleware) {
            $next = fn($req, $res) => $middleware($req, $res, $next);
        }

        try {
            // Run the fully composed chain
            return $next($request, $response);
        } catch (\Throwable $th) {
            // TODO: Should we emit an error event here?
            error_log("Middleware Error: " . $th->getMessage());
            $response->status(500)->send("Internal Server Error");
            die();
        }
    }
}
