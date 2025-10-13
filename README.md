🧊 Flake
<p align="center"> <img src="art/logo.png?v1" alt="Flake Framework Logo" width="240"> </p> <p align="center"> <strong>A minimal PHP microframework inspired by Express.js — simple, fast, and easy to extend.</strong> </p>

## 🚀 Installation

Before you start, make sure you have PHP ≥ 8.1 and Composer installed.

```bash
mkdir myapp
cd myapp
composer init
composer require iescarro/flake
```

## 🧩 Quick Start

Create an index.php file:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Flake\Router;
use Flake\App;

Router::get('/', function ($req, $res) {
    $res->send('Hello, world!');
});

App::run();
```

Then start the local server:

```bash
php -S localhost:8000
```

Open your browser at 👉 http://localhost:8000

You should see “Hello, world!”

## ⚙️ Features

* 🚦 Express-style routing — Simple Router::get(), Router::post(), etc.
* 💡 Minimal core — Focused on speed, readability, and flexibility.
* 🧱 Extensible — Add your own middleware, handlers, or modules.
* 🧰 No configuration required — Works out of the box.

## 🧠 Example Routes

```php
Router::get('/hello/:name', function ($req, $res) {
    $name = $req->params['name'] ?? 'Guest';
    $res->send("Hello, $name!");
});

Router::post('/data', function ($req, $res) {
    $res->json(['received' => $req->body]);
});
```

## 📚 Learn More

Visit the [Wiki → Home](https://github.com/iescarro/flake/wiki) for deeper examples.
* Routing and middleware
* JSON responses
* Request and response objects
* Error handling and custom middleware

## 🧑‍💻 Contributing

Contributions, issues, and feature requests are welcome!

Feel free to open a discussion or pull request
.

## 📜 License

Released under the MIT License

Copyright © 2025 FlakePHP
.
