<?php
namespace Flake;

class Request
{
    protected static array $params = [];
    public ?Session $session       = null;
    /**
     * Get Parameters
     *
     * @return array
     */
    public static function getParams(): array
    {
        return self::$params;
    }

    /**
     * Get Route Parameter
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getParam(string $name, $default = null): mixed
    {
        return self::$params[$name] ?? $default;
    }

    /**
     * Set Parameter
     *
     * @param string $name
     * @param mixed $value
     */
    public static function setParam(string $name, $value): void
    {
        self::$params[$name] = $value;
    }

    /**
     * Set Parameters
     *
     * @param array $params
     */
    public static function setParams(array $params)
    {
        self::$params = array_merge(self::$params, $params);
    }

    /**
     * Check if parameter exists
     *
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return self::findParam(self::$params, explode('.', $name), null) !== null;
    }

    /**
     * Get Parameter, support dot notation
     *
     * @param string $name
     * @param mixed $default
     * @param bool $explicit If true, only search in the query parameters ($_GET), otherwise search in $_REQUEST and raw body JSON
     * @return mixed
     */
    public static function get(string $name, $default = null, bool $explicit = false): mixed
    {
        return self::findParam($explicit ? $_GET : self::$params, explode('.', $name), $default);
    }

    /**
     * Post Parameter, support dot notation
     *
     * @param string $name
     * @param mixed $default
     * @param bool $explicit If true, only search in the form parameters ($_POST), otherwise search in both $_POST and raw body JSON
     * @return mixed
     */
    public static function post(string $name, $default = null, bool $explicit = false): mixed
    {
        $path  = explode('.', $name);
        $array = $_POST;
        if (strlen(self::body()) > 0 && ! $explicit) {
            $array = array_merge($array, self::json());
        }

        return self::findParam($array, $path, $default);
    }

    /**
     * Cookie Parameter, support dot notation
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function cookie(string $name, $default = null): mixed
    {
        return self::findParam($_COOKIE, explode('.', $name), $default);
    }

    /**
     * Find Parameter
     *
     * @param array $array
     * @param array $path
     * @param mixed $default
     * @return mixed
     */
    protected static function findParam(array $array, array $path, $default)
    {
        $key = array_shift($path);
        if (array_key_exists($key, $array)) {
            if (count($path) === 0) {
                return $array[$key];
            }
            if (is_array($array[$key])) {
                return self::findParam($array[$key], $path, $default);
            }
            return $default;
        }
        return $default;
    }

    /**
     * Get URI
     *
     * @return string
     */
    public static function uri(): string
    {
        return strtok($_SERVER['REQUEST_URI'], '?');
    }

    /**
     * Get requested method
     *
     * @return string
     */
    public static function method(): string
    {
        $realMethod = $_SERVER['REQUEST_METHOD'];
        if ($realMethod === 'POST' && isset($_POST['_method'])) {
            $realMethod = strtoupper($_POST['_method']);
        }
        return $realMethod;
    }

    /**
     * Get raw body
     *
     * @return string
     */
    public static function body(): string
    {
        return file_get_contents('php://input');
    }

    /**
     * Get JSON body
     *
     * @return array
     */
    public static function json(): array
    {
        $decoded = json_decode(self::body(), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return [];
    }

    /**
     * Get POST Parameter
     *
     * @return mixed
     */
    public static function files(): array
    {
        return $_FILES;
    }

    /**
     * Get file
     *
     * @param string $name
     * @return array
     */
    public static function file($name): array
    {
        return $_FILES[$name] ?? [];
    }

    /**
     * Magic call to allow instance method calls
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return self::{$name}(...$arguments);
    }
}
