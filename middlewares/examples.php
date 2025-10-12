<?php

use Flake\Middleware;
use Flake\Request;
use Flake\Response;

// Middleware example
Middleware::use(function (Request $req, Response $res, $next) {
    // Simple logging middleware
    error_log("Request: " . $req->method() . " " . $req->uri());
    return $next($req, $res);
});

// CORS middleware
Middleware::use(function (Request $req, Response $res, $next) {
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
