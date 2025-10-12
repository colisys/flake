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
        // Support controller class callbacks like [ProductController::class, 'destroy']
        if (is_array($callback) && count($callback) === 2 && class_exists($callback[0])) {
            $instance = new $callback[0]();
            $callback = [$instance, $callback[1]];
        }

        if (!is_callable($callback)) {
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
            // Check if exact route exists
            if (array_key_exists($uri, $routes[$method]) && is_callable($routes[$method][$uri])) {
                $callback = $routes[$method][$uri];
            } else {
                // Check for parameterized routes
                foreach ($routes[$method] as $route => $cb) {
                    // Capture parameter names like :id
                    preg_match_all('#:([\w]+)#', $route, $paramNames);

                    // Convert route to regex
                    $routeRegex = preg_replace('#:([\w]+)#', '([\w-]+)', $route);

                    // Match against requested URI
                    if (preg_match("#^{$routeRegex}$#", $uri, $matches)) {
                        $callback = $cb;

                        // Set route parameters in request
                        foreach ($paramNames[1] as $index => $name) {
                            // $matches[0] is full match, $matches[1..] are capture groups
                            if (isset($matches[$index + 1])) {
                                $request->setParam($name, $matches[$index + 1]);
                            }
                        }
                        break;
                    }
                }
            }
        }

        // Call the callback with request, response, and route parameters
        call_user_func(
            $callback,
            $request,
            $response,
            ...array_values($request->getParams())
        );
    }
}
