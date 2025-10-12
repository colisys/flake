<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Flake\App;
use Flake\Middleware;
use Flake\Request;
use Flake\Response;
use Flake\Router;

Router::post('/data/:id/meow', function (Request $req, Response $res, $id) {
    // print_r($req->getParams());
    // $id   = $req->get('id', 'unknown');
    $body = $req->body();
    $res->json([
        'id'            => $req->get('id', 'unknown'),
        'received_id'   => $id,
        'received_body' => json_decode($body, true),
        'message'       => $req->post('data.message', 'Hello World!'),
    ]);
});

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

// 404 route example (optional)
Router::fallback(function (Request $req, Response $res) {
    $res->status(404)->send('<h1>404 Not Found</h1><p>The route you requested does not exist.</p>');
});

// Middleware example
Middleware::use (function (Request $req, Response $res, $next) {
    // Simple logging middleware
    error_log("Request: " . $req->method() . " " . $req->uri());
    return $next($req, $res);
});

// CORS middleware
Middleware::use (function (Request $req, Response $res, $next) {
    if ($req->method() === 'OPTIONS') {
        // Simple CORS middleware
        $res->header('Access-Control-Allow-Origin', '*');
        $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $res->status(204)->send('');
        return;
    }
    return $next($req, $res);
});

// Start the app
App::run();
