<?php

namespace Flake;

use ReflectionFunction;

class Router
{
    /**
     * @var array<string, array<string, \Closure(Request $request, Response $response)|array{0: class-string, 1: string}>>
     */
    private static array $routes = [];

    /**
     * @var ?\Closure(Request $request, Response $response)
     */
    private static $fallback = null;

    /**
     * @var ?\Closure(Request $request, Response $response)
     */
    private static $error = null;

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
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     */
    public static function get($uri, $callback)
    {
        self::any('GET', $uri, $callback);
    }

    /**
     * Add a POST route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     */
    public static function post($uri, $callback)
    {
        self::any('POST', $uri, $callback);
    }

    /**
     * Add a PUT route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     */
    public static function put($uri, $callback)
    {
        self::any('PUT', $uri, $callback);
    }

    /**
     * Add a PATCH route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     */
    public static function patch($uri, $callback)
    {
        self::any('PATCH', $uri, $callback);
    }

    /**
     * Add a DELETE route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     */
    public static function delete($uri, $callback)
    {
        self::any('DELETE', $uri, $callback);
    }

    /**
     * Add a route for any method
     *
     * @param string|array{"GET": 0, "POST": 1, "PUT": 2, "PATCH": 3, "DELETE": 4} $method
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     */
    public static function any($method, $uri, $callback)
    {
        if (is_callable($callback) === false && ! (is_array($callback) && count($callback) === 2)) {
            throw new \InvalidArgumentException('Callback must be callable');
        }
        if (is_array($method)) {
            foreach ($method as $m) {
                self::$routes[$m][$uri] = $callback;
            }
        } else {
            self::$routes[$method][$uri] = $callback;
        }
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

    /**
     * Dispatch the request to the appropriate route
     *
     * @param Request $request
     * @param Response $response
     */
    public static function dispatch(Request $request, Response $response): void
    {
        $uri      = $request->uri();
        $method   = $request->method();
        $routes   = self::$routes;
        $callback = self::$fallback ?? fn($response) => $response->setStatus(404)->send('404 Not Found');

        try {
            // Ensure method exists
            if (! isset($routes[$method])) {
                throw new \InvalidArgumentException("Method {$method} not supported");
            }

            // Check if simple route exists
            if (array_key_exists($uri, $routes[$method])) {
                if (
                    isset($routes[$method]) &&
                    is_callable($routes[$method][$uri]) ||
                    count($routes[$method][$uri]) === 2
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

            // Reflect the callback to inject parameters
            if (is_callable($callback)) {
                $handler = new ReflectionFunction($callback);
            } else if (count($callback) === 2) {
                $handler = new \ReflectionMethod($callback[0], $callback[1]);
            }

            if ($handler === null) {
                throw new \InvalidArgumentException("Handler is not callable, or not found, got " . gettype($callback));
            }

            $invokeArgs = [];
            foreach ($handler->getParameters() as $rparam) {
                $paramName = $rparam->getName();

                // Handle Request and Response injection
                if ($rparam->getType() instanceof \ReflectionNamedType) {
                    $typeName = $rparam->getType()->getName();
                    if ($typeName === Request::class) {
                        $invokeArgs[$paramName] = $request;
                        continue;
                    }
                    if ($typeName === Response::class) {
                        $invokeArgs[$paramName] = $response;
                        continue;
                    }

                    // Validate non-builtin types early
                    if (! $rparam->getType()->isBuiltin()) {
                        throw new \InvalidArgumentException("Parameter {$paramName} must be a scalar type");
                    }
                }

                // First check route parameters and query/post parameters
                $paramValue = $request->getParam($paramName) ?? $request->get($paramName);

                // If no value found, handle defaults
                if ($paramValue === null) {
                    if ($rparam->isDefaultValueAvailable()) {
                        // Use parameter's default value from method signature
                        $paramValue = $rparam->getDefaultValue();
                    } else if (! $rparam->isOptional()) {
                        // Required parameter not provided
                        throw new \InvalidArgumentException("Parameter {$paramName} is required");
                    } else {
                        // Optional parameter without default - use type default
                        $paramValue = match ($rparam->getType()->getName()) {
                            'int'    => 0,
                            'float', 'double' => 0.0,
                            'string' => '',
                            'bool'   => false,
                            'array'  => [],
                            default  => null,
                        };
                    }
                }

                $invokeArgs[$paramName] = $paramValue;
            }

            if ($handler instanceof \ReflectionMethod  && $handler->isStatic() === false) {
                $instance = new ($handler->getDeclaringClass()->getName());
                $handler  = $handler->getClosure($instance);
            } else {
                $handler = $handler->getClosure();
            }

            $handler(...$invokeArgs);
        } catch (\Throwable $th) {
            if (isset(self::$error) && is_callable(self::$error)) {
                $errorHandler = self::$error;
                $errorHandler($request, $response, $th);
            } else {
                // Default error handling
                error_log("Error captured, code: " . $th->getCode() . ', message: ' . $th->getMessage() . PHP_EOL .
                    '== Trace Begin == ' . PHP_EOL . PHP_EOL . $th->getTraceAsString() . PHP_EOL . PHP_EOL . '== Trace End ==');
                $response->status(500)->send("Internal Server Error");
            }
            die();
        }
    }
}
