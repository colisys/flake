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
        $uri    = $request->uri();
        $method = $request->method();
        $routes = self::$routes;

        // Default 404 fallback (Express-style)
        $callback = self::$fallback ?? function ($req, $res) {
            $res->status(404)->send("Cannot " . $req->method() . " " . $req->uri());
        };

        // Match route
        if (isset($routes[$method])) {
            // Exact match
            if (isset($routes[$method][$uri]) && is_callable($routes[$method][$uri])) {
                $callback = $routes[$method][$uri];
            } else {
                // Check for parameterized routes
                foreach ($routes[$method] as $route => $cb) {
                    preg_match_all('#:([\w]+)#', $route, $paramNames);
                    $routeRegex = preg_replace('#:([\w]+)#', '([\w-]+)', $route);

                    if (preg_match("#^{$routeRegex}$#", $uri, $matches)) {
                        $callback = $cb;
                        foreach ($paramNames[1] as $i => $name) {
                            if (isset($matches[$i + 1])) {
                                $request->setParam($name, $matches[$i + 1]);
                            }
                        }
                        break;
                    }
                }
            }
        }

        call_user_func($callback, $request, $response, ...array_values($request->getParams()));
    }
}
