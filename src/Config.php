<?php

namespace Flake;

use Flake\Event\Builtin\ConfigUpdateEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class Config
{

    protected static array $config = [];

    public function __construct(string $basePath)
    {
        self::init($basePath);
    }

    public static function init(string $basePath)
    {
        if (file_exists($basePath . "/config")) {
            foreach (glob($basePath . "/config/*.php") as $file) {
                self::set(basename($file, ".php"), require $file);
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $config = self::$config;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($config[$key]) || !is_array($config[$key])) {
                return $default;
            }
            $config = $config[$key];
        }
        return $config[array_shift($keys)] ?? $default;
    }

    public static function set(string $key, $value)
    {
        $keys = explode('.', $key);
        $config = &self::$config;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($config[$key]) || !is_array($config[$key])) {
                $config[$key] = [];
            }
            $config = &$config[$key];
        }
        $key = array_shift($keys);
        $config[$key] = $value;

        make(EventDispatcherInterface::class)?->dispatch(new ConfigUpdateEvent($key, $value));
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

if (!function_exists("config")) {
    /**
     * Get config value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return \Flake\Config::get($key, $default);
    }
}
