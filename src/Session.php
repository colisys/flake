<?php

namespace Flake;

class Session
{
    /**
     * @param array{"save_path":string,"prefix":string} $options
     */
    public function __construct(array $options = [])
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_save_path($options['save_path'] ?? sys_get_temp_dir());
            session_id(session_create_id($options['prefix'] ?? "flake"));
            session_start();
        }

        // Remove old flash data after reading
        if (isset($_SESSION['_flash_old'])) {
            unset($_SESSION['_flash_old']);
        }

        if (isset($_SESSION['_flash_new'])) {
            $_SESSION['_flash_old'] = $_SESSION['_flash_new'];
            unset($_SESSION['_flash_new']);
        }
    }

    // Set flash data
    public function flash(string $key, $value): void
    {
        $_SESSION['_flash_new'][$key] = $value;
    }

    // Get flash data
    public function getFlash(string $key, $default = null)
    {
        return $_SESSION['_flash_old'][$key] ?? $default;
    }

    // General session helpers
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        session_destroy();
    }

    public function getId(): string
    {
        return session_id();
    }
}
