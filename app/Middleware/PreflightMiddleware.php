<?php

namespace App\Middleware;

use Flake\Contract\AbstractMiddleware;
use Flake\Request;
use Flake\Response;

use function Flake\dd;

class PreflightMiddleware extends AbstractMiddleware
{
    public function handle(Request $request, Response $response, callable $next)
    {
        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->truncate();
            return;
        }

        return $next($request, $response);
    }
}
