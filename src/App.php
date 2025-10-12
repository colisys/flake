<?php

namespace Flake;

class App
{
    static function run()
    {
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $method = $_SERVER['REQUEST_METHOD'];

        $routes = Router::routes();

        if (isset($routes[$method]) && array_key_exists($uri, $routes[$method])) {
            $callback = $routes[$method][$uri];

            $request = new Request();
            $response = new Response();

            call_user_func($callback, $request, $response);
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
    }
}
