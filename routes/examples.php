<?php

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
        'id'  => $req->get('id', 'unknown'),
        // Get route parameter
        'received_id' => $id,
    ]);
});

// Route with parameter
Router::post('/user/:id', function (Request $req, Response $res, $id) {
    // All route parameters will injected as function arguments
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
    $res->status(404)->send('<h1>404 Not Found</h1><p>The route you requested does not exist.</p>');
});
