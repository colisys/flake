<?php

namespace Flake;

class Env
{
    protected static array $env = [];

    public function __construct(string $basePath)
    {
        self::init($basePath);
    }

    public static function init(string $basePath)
    {
        if ($envs = file($basePath . '/.env')) {
            foreach ($envs as $env) {
                if (str_starts_with($env, '#')) {
                    continue;
                }
                $env = explode('=', $env);
                self::$env[trim($env[0])] = trim($env[1]);
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$env[$key] ?? $default;
    }

    public static function set(string $key, $value)
    {
        self::$env[$key] = $value;
    }

    public static function all(): array
    {
        return self::$env;
    }

    public static function has(string $key): bool
    {
        return isset(self::$env[$key]);
    }

    public function __call($name, $arguments)
    {
        return self::{$name}(...$arguments);
    }
}

if (!function_exists("env")) {
    /**
     * Get environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return Env::get($key, $default);
    }
}
