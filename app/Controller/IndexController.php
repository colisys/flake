<?php
namespace App\Controller;

use Flake\Request;
use Flake\Response;

class IndexController
{
    public function hello(Response $response): void
    {
        $response->json(['message' => 'Hello, World!']);
    }
}
