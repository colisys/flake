<?php

namespace Flake;

class Env
{
    protected static array $config = [];

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
                self::$config[trim($env[0])] = trim($env[1]);
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    public static function set(string $key, $value)
    {
        self::$config[$key] = $value;
    }

    public static function all(): array
    {
        return self::$config;
    }

    public static function has(string $key): bool
    {
        return isset(self::$config[$key]);
    }

    public function __call($name, $arguments)
    {
        return self::{$name}(...$arguments);
    }
}
