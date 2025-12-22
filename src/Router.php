<?php

namespace Flake;

use Flake\DI\ApplicationContext;
use Flake\Exceptions\NotSupportHTTPMethodException;
use Flake\Exceptions\RouterDispatchException;

class Router
{
    private const CALLBACK_WITH_TYPE_HINT       = 1;
    private const CALLBACK_WITH_NO_TYPE_HINT    = 2;
    private const CALLBACK_WITH_MIXED_TYPE_HINT = 3;

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
            'GET'     => [],
            'POST'    => [],
            'PUT'     => [],
            'DELETE'  => [],
            'PATCH'   => [],
            'OPTIONS' => [],
        ];
    }

    /**
     * Add a GET route
     *
     * @param string $uri
     * @param \Closure(Request $request, Response $response, ...$params)|array{0: class-string, 1: string} $callback
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
     */
    public static function any($method, $uri, $callback)
    {
        match (true) {
            is_callable($callback)                    => true,
            (is_array($callback) && count($callback) === 2) &&
                method_exists($callback[0], $callback[1]) => true,
            default                                   => throw new \InvalidArgumentException('Callback must be callable')
        };

        // TODO: Should we throw an error if the router already registed?
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
     * Build the router and get proper handler
     *
     * @param Request $request
     * @param Response $response
     * @return \Closure(Request $request, Response $response)
     * @throws NotSupportHTTPMethodException
     */
    public static function buildRouter(Request $request, Response $response)
    {
        $uri     = $request->uri();
        $method  = $request->method();
        $routes  = self::$routes;

        // Default 404 fallback (Express-style)
        $handler = self::$fallback ?? function ($req, $res) {
            $res->status(404)->send("Cannot " . $req->method() . " " . $req->uri());
        };

        // Ensure method exists
        if (! isset($routes[$method])) {
            throw new NotSupportHTTPMethodException("Method {$method} not supported");
        }

        // Check if simple route exists
        if (array_key_exists($uri, $routes[$method])) {
            if (
                isset($routes[$method]) &&
                is_callable($routes[$method][$uri]) ||
                count($routes[$method][$uri]) === 2
            ) {
                $handler = $routes[$method][$uri];
            }
        } else {
            // Check for parameterized routes
            foreach ($routes[$method] as $route => $cb) {
                // separate parameter name and value using regex
                preg_match_all('#:([\w]+)(?:/)?#', $route, $paramNames);
                // Convert :param to regex
                $pattern = preg_replace('#:([\w]+)#', '([\w-]+)', $route);
                // Check for optional parameters, only last one is allowed
                $pattern2 = preg_replace('#\?\(\[\\\w\-\]\+\)$#', '?', $pattern);
                // Need clean pattern for optional parameters
                $pattern = preg_replace('#\?#', '', $pattern);
                // Check if route matches
                if (preg_match("#^{$pattern}$#", $uri, $matches) || preg_match("#^{$pattern2}$#", $uri, $matches2)) {
                    $matches = array_merge($matches ?? [], $matches2 ?? []);
                    if (count($matches) > 0) {
                        // Remove unnessary element
                        array_shift($matches);
                        $paramNames = array_pop($paramNames);

                        $handler = $cb;
                        foreach ($paramNames as $index => $name) {
                            $request->setParam($name, $matches[$index] ?? null);
                        }

                        break;
                    }
                }
            }
        }

        return $handler;
    }

    /**
     * Dispatch the request to the appropriate route
     *
     * @param Request $request
     * @param Response $response
     * @param \Closure(...$args)|array{0: class-string, 1: string} $callback
     * @throws RouterDispatchException
     */
    public static function dispatch(Request $request, Response $response, $callback): void
    {
        try {
            // Reflect the callback to inject parameters
            if (is_callable($callback)) {
                $handler = new \ReflectionFunction($callback);
            } elseif (count($callback) === 2) {
                $handler = new \ReflectionMethod($callback[0], $callback[1]);
            }

            // We walk through the parameters first, since we don't know how many parameters the callback has
            // Also we need to inject Request and Response by type
            // What about callbacks with no type hint? We may need to handle them differently here
            $invokeArgs = match (self::checkCallback($handler)) {
                self::CALLBACK_WITH_NO_TYPE_HINT => self::defaultNoTypeHintCallback($handler, $request, $response),
                default                          => self::defaultTypeHintCallback($handler, $request, $response),
            };

            // If the handler is a non-static method, instantiate the class
            // and get the closure from the instance
            if ($handler instanceof \ReflectionMethod  && $handler->isStatic() === false) {
                $instance = make($handler->getDeclaringClass()->getName());
                $handler  = $handler->getClosure($instance);
            } else {
                $handler = $handler->getClosure();
            }

            // Finally, invoke the handler with the prepared arguments
            $handler(...$invokeArgs);
        } catch (\Throwable $th) {
            if ($th instanceof \ReflectionException) {
                dd($th);
            }

            // TODO: need to dispatch an event?
            if (isset(self::$error) && is_callable(self::$error)) {
                $errorHandler = self::$error;
                $errorHandler($request, $response, $th);
            } else {
                $response->status(500)->send("Internal Server Error");
                throw new RouterDispatchException($th->getMessage(), $th->getCode(), $th);
            }
        }
    }

    /**
     * Default callback for type hinting
     *
     * @param \ReflectionFunction | \ReflectionMethod $handler
     * @param Request $request
     * @param Response $response
     * @return array
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    protected static function defaultTypeHintCallback(\ReflectionFunction  | \ReflectionMethod $handler, Request $request, Response $response): array
    {
        $invokeArgs = self::defaultNoTypeHintCallback($handler, $request, $response);
        foreach ($handler->getParameters() as $rparam) {
            $paramName = $rparam->getName();
            if ($rparam->getType() instanceof \ReflectionNamedType && !$rparam->getType()->isBuiltin()) {
                $invokeArgs[$paramName] = ApplicationContext::make($rparam->getType()->getName(), []);
            }
        }
        return $invokeArgs;
    }

    /**
     * Default callback for no type hinting
     *
     * @param \ReflectionFunction | \ReflectionMethod $handler
     * @param Request $request
     * @param Response $response
     * @return array
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    protected static function defaultNoTypeHintCallback(\ReflectionMethod  | \ReflectionFunction $handler, Request $request, Response $response): array
    {
        $invokeArgs = [];
        foreach ($handler->getParameters() as $index => $rparam) {
            $paramName = $rparam->getName();
            // Only for first two parameters will be Request and Response
            if ($index < 2) {
                if (preg_match('#^(R|r)eq.*#', $paramName) != false) {
                    $invokeArgs[$paramName] = $request;
                    continue;
                }

                if (preg_match('#^(R|r)es.*#', $paramName) != false) {
                    $invokeArgs[$paramName] = $response;
                    continue;
                }
            }

            // First check route parameters and query/post parameters
            $paramValue = $request->getParam($paramName) ?? $request->get($paramName);

            // If no value found, handle defaults
            if ($paramValue === null) {
                if ($rparam->isDefaultValueAvailable()) {
                    // Use parameter's default value from method signature
                    $paramValue = $rparam->getDefaultValue();
                } elseif (! $rparam->isOptional()) {
                    if ($rparam->getType() instanceof \ReflectionNamedType) {
                        if ($rparam->getType()->allowsNull()) {
                            $paramValue = null;
                        } else if ($rparam->getType()->isBuiltin()) {
                            throw new \InvalidArgumentException("Parameter {$paramName} is required");
                        }
                    }
                } else {
                    // Optional parameter without default - use type default
                    $paramValue = match ($rparam->getType()?->getName()) {
                        'int'    => 0,
                        'float', 'double' => 0.0,
                        'string' => '',
                        'bool'   => false,
                        'array'  => [],
                        default  => null,
                    };
                }
            }

            $request->setParam($paramName, $paramValue);
            $invokeArgs[$paramName] = $paramValue;
        }
        return $invokeArgs;
    }

    /**
     * Check callback type
     *
     * @param \ReflectionMethod | \ReflectionFunction $handler
     * @return int
     */
    protected static function checkCallback(\ReflectionMethod  | \ReflectionFunction $handler): int
    {
        $typeHint = false;
        $mixed    = false;
        foreach ($handler->getParameters() as $rparam) {
            if ($rparam->getType() instanceof \ReflectionNamedType) {
                $typeHint = true;
                if (! $rparam->getType()->isBuiltin()) {
                    $mixed = true;
                }
            }
        }

        return match (true) {
            $typeHint && ! $mixed => self::CALLBACK_WITH_TYPE_HINT,
            $mixed               => self::CALLBACK_WITH_MIXED_TYPE_HINT,
            default              => self::CALLBACK_WITH_NO_TYPE_HINT,
        };
    }
}
