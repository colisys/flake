<?php

namespace Flake;

class Request
{
    protected array $parameters;

    public function __construct()
    {
        // For simplicity, we'll merge GET and POST into one place
        $this->parameters = array_merge($_GET, $_POST);
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    function uri()
    {
        return strtok($_SERVER['REQUEST_URI'], '?');
    }
}
