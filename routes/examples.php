<?php

use Flake\Events;
use Flake\Request;
use Flake\Response;
use Flake\Router;

// Home route
Router::get('/', function ($req, $res) {
    $output = "
        <h1>Flake v0.0.1 is running!</h1>
        <p>Welcome to your new PHP microframework inspired by Express.js.</p>
        <ul>
            <li>Try <a href=\"/hello?name=Flake\">/hello?name=Flake</a></li>
            <li>Try <a href=\"/json\">/json</a></li>
            <li>Try a 404: <a href=\"/unknown\">/unknown</a></li>
        </ul>
    ";
    $res->status(200)->send($output);
});

// Simple text route
Router::get('/hello', function (Request $req, Response $res) {
    $name = $req->get('name', 'World');
    $res->send("Hello, {$name}!");
});

// JSON route
Router::get('/json', function (Request $req, Response $res) {
    $res->json([
        'message'   => 'Welcome to Flake!',
        'version'   => '1.0.0',
        'timestamp' => time(),
    ]);
});

// Get route with parameter
Router::get('/user/:id', function (Request $req, Response $res, $id) {
    // All route parameters will injected as function arguments
    $res->json([
        // Get query parameter
        'id'          => $req->get('id', 'unknown'),
        // Get route parameter
        'received_id' => $id,
    ]);
});

// Route that will not respond, but will log a message
// Therefore, the Response object can be ignored, the sorting of parameters does not matter
Router::get('/will-not-respond', function (Request $req) {
    if ($req->get('log', '0') === '1') {
        error_log('This route was accessed but will not respond');
    }
});

// You can also ignore the Response object
Router::get('/will-not-receive', function (Response $res) {
    error_log('This route will not receive');
    $res->send('Now I am responding!');
});

// Route using a controller class
Router::get('/class-route', [\App\Controller\IndexController::class, 'hello']);

// Registing to multiple methods
Router::any(['GET', 'POST'], '/multi-method', function (Request $req, Response $res) {
    $res->json([
        'method' => $req->method(),
        'msg'    => 'This route supports both GET and POST',
    ]);
});

// Route with parameter
// All route parameters (only route parameters) will injected as function arguments as strings
// You can specify the type of the parameter, but only scalar types are supported (int, float, string, bool)
// If the type does not match, an InvalidArgumentException will be thrown, resulting a 500 error
Router::post('/user/:id', function (Request $req, Response $res, int $id, string $unused = 'default') {
    $body = $req->body();
    $res->json([
        // Get query parameter
        'id'            => $req->get('id', 'unknown'),
        // Get route parameter
        'received_id'   => $id,
        // Get raw body and parse it as JSON
        'received_body' => json_decode($body, true),
        // Get nested data using dot notation
        'message'       => $req->post('data.message', 'Hello World!'),
        // Also works with index
        'list'          => $req->post('data.list.2'),
        // Optional parameter with default value also works, but it's not a part of the route parameters
        // So the value will always be the default value unless specified in the route or query parameters
        // Tips: try "/user/:id/anything/?:unused"
        'unused'        => $unused,
    ]);
});

// PUT route example
Router::put('/user/:id', function (Request $req, Response $res, $id) {
    $data = $req->json();
    $res->json([
        'method' => 'PUT',
        'id'     => $id,
        'data'   => $data,
    ]);
});

// DELETE route example
Router::delete('/user/:id', function (Request $req, Response $res, $id) {
    $res->json([
        'method' => 'DELETE',
        'id'     => $id,
    ]);
});

// 404 route example (optional)
Router::fallback(function (Request $req, Response $res) {
    // You can fireup events and do other stuff
    Events::dispatch("Router.NotFound", new \Exception("Route not found on requested method={$req->method()}, uri={$req->uri()}"));
    $res->status(404)->send('<h1>404 Not Found</h1><p>The route you requested does not exist.</p>');
});
