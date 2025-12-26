<?php

namespace Flake\Contract;

use Flake\Request;
use Flake\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next);
}
