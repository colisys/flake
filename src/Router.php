<?php
namespace Flake;

class Router
{
    /**
     * @var array<string, array<string, \Closure(Request $request, Response $response)|\Closure(Request $request, Response $response, Middleware $next)>>
     */
    private static array $routes = [];

    /**
     * @var \Closure(Request $request, Response $response)
     */
    private static $fallback = null;

    public function __construct()
    {
        $this->routes = [
            'GET'    => [],
            'POST'   => [],
            'PUT'    => [],
            'DELETE' => [],
            'PATCH'  => [],
        ];
    }

    /**
     * Add a GET route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params) $callback
     */
    public static function get($uri, $callback)
    {
        self::any('GET', $uri, $callback);
    }

    /**
     * Add a POST route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params) $callback
     */
    public static function post($uri, $callback)
    {
        self::any('POST', $uri, $callback);
    }

    /**
     * Add a PUT route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params) $callback
     */
    public static function put($uri, $callback)
    {
        self::any('PUT', $uri, $callback);
    }

    /**
     * Add a PATCH route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params) $callback
     */
    public static function patch($uri, $callback)
    {
        self::any('PATCH', $uri, $callback);
    }

    /**
     * Add a DELETE route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params) $callback
     */
    public static function delete($uri, $callback)
    {
        self::any('DELETE', $uri, $callback);
    }

    /**
     * Add a route for any method
     *
     * @param string $method
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params) $callback
     */
    public static function any($method, $uri, $callback)
    {
        if (is_callable($callback) === false) {
            throw new \InvalidArgumentException('Callback must be callable');
        }
        self::$routes[$method][$uri] = $callback;
    }

    /**
     * Set the fallback route
     *
     * @param \Closure(Request $request, Response $response) $callback
     */
    public static function fallback(callable $callback): void
    {
        self::$fallback = $callback;
    }

    public static function dispatch(Request $request, Response $response): void
    {
        $uri      = $request->uri();
        $method   = $request->method();
        $routes   = self::$routes;
        $callback = self::$fallback ?? fn($request, $response) => $response->setStatus(404)->send('404 Not Found');

        // Ensure method exists
        if (isset($routes[$method])) {
            // Check if simple route exists
            if (array_key_exists($uri, $routes[$method])) {
                if (
                    isset($routes[$method]) &&
                    array_key_exists($uri, $routes[$method]) &&
                    is_callable($routes[$method][$uri])
                ) {
                    $callback = $routes[$method][$uri];
                }
            } else {
                // Check for parameterized routes
                foreach ($routes[$method] as $route => $cb) {
                    // separate parameter name and value using regex
                    preg_match('#:([\w]+)#', $route, $paramNames);
                    // Convert :param to regex
                    $route = preg_replace('#:([\w]+)#', '([\w-]+)', $route);
                    // Check if route matches
                    if (preg_match("#^{$route}$#", $uri, $matches)) {
                        $callback = $cb;
                        foreach ($paramNames as $index => $name) {
                            if ($index === 0) {
                                continue;
                            }
                            // Skip full match
                            $request->setParam($name, $matches[$index]);
                        }
                        break;
                    }
                }
            }
        }

        call_user_func($callback, $request, $response, ...$request->getParams());
    }
}
