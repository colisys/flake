# 🧊 Flake

<p align="center">
<img src="art/logo.png?v1" alt="Flake Framework Logo" width="250"/>
</p>

A minimal PHP microframework inspired by Express.js — simple, fast, and easy to extend.

## 🚀 Installation

Create a new project using Composer:

```bash
composer create-project iescarro/flake myapp
```

Then start the development server:

```bash
cd myapp
composer start
```

Visit: http://localhost:8000

## 📁 Directory Structure

```
myapp/
├── composer.json
├── public/
│ └── index.php
└── src/
├── App.php
├── Router.php
├── Request.php
└── Response.php
```

## 🧩 Example Routes (public/index.php)

```php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Flake\Request;
use Flake\Response;
use Flake\Router;
use Flake\App;

Router::get('/', function (Request $req, Response $res) {
    $res->status(200)->send("
        <h1>Flake v1.0.0</h1>
        <p>Welcome to your minimal PHP microframework!</p>
        <ul>
            <li><a href=\"/hello?name=Flake\">/hello</a></li>
            <li><a href=\"/json\">/json</a></li>
        </ul>
    ");
});

Router::get('/hello', function (Request $req, Response $res) {
    $name = $req->get('name', 'World');
    $res->send("Hello, {$name}!");
});

Router::get('/json', function (Request $req, Response $res) {
    $res->json([
        'message' => 'Welcome to Flake!',
        'version' => '0.0.1'
    ]);
});

Router::fallback(function (Request $req, Response $res) {
    $res->status(404)->send('<h1>404 Not Found</h1><p>That route does not exist.</p>');
});

Middleware::use(function (Request $req, Response $res, $next) {
    error_log("Request: " . $req->method() . " " . $req->uri());
    return $next($req, $res);
});

Middleware::use(function (Request $req, Response $res, $next) {
    if ($req->method() === 'OPTIONS') {
        $res->header('Access-Control-Allow-Origin', '*');
        $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        return $res->status(204)->send('');
    }
    return $next($req, $res);
});

App::run();
```

## 🧱 Middleware Example

You can chain multiple middleware just like in Express:

```php
Middleware::use(function ($req, $res, $next) {
    error_log('Before route');
    $next($req, $res);
error_log('After route');
});
```

## 🧪 Development

Run tests or the server with Composer:

```
composer start
```

## 📜 License

Licensed under the MIT License.
Copyright © 2025 FlakePHP
