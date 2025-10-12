<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Flake\Request;
use Flake\Response;
use Flake\Router;
use Flake\App;

// Home route
Router::get('/', function (Request $req, Response $res) {
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
        'message' => 'Welcome to Flake!',
        'version' => '1.0.0',
        'timestamp' => time()
    ]);
});

// 404 route example (optional)
Router::fallback(function (Request $req, Response $res) {
    $res->status(404)->send('<h1>404 Not Found</h1><p>The route you requested does not exist.</p>');
});

// Start the app
App::run();
