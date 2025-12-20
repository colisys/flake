<?php

namespace Flake;

class Events
{
    /**
     * @var array<string, callable|array{0: class-string, 1: string}[]>
     */
    protected static array $listeners = [];

    public static function listen(string $event, callable $listener)
    {
        self::$listeners[$event][] = $listener;
    }

    public static function hasListeners(string $event): bool
    {
        return isset(self::$listeners[$event]);
    }

    public static function removeListener(string $event, callable $listener)
    {
        if (isset(self::$listeners[$event])) {
            $index = array_search($listener, self::$listeners[$event]);
            if ($index !== false) {
                unset(self::$listeners[$event][$index]);
            }
        }
    }

    public static function getListeners(string $event): array
    {
        return self::$listeners[$event] ?? [];
    }

    public static function dispatch(string $event, ...$args)
    {
        $listeners = self::getListeners($event);
        foreach ($listeners as $listener) {
            if (is_string($listener) && class_exists($listener)) {
                $rmethod = new \ReflectionMethod($listener, 'handle');
                $rmethod->invokeArgs(new ($listener), $args);
            } else {
                $listener(...$args);
            }
        }
    }
}
