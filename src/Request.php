<?php

namespace Flake;

class Request
{
    protected array $params  = [];
    protected array $headers = [];
    public Session $session;

    public function __construct()
    {
        $this->session = make(Session::class);
        $this->headers = $this->headers();
        // TODO: We should check if the request is valid
        $this->setParams($_REQUEST);
    }

    /**
     * Get Parameters
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get Route Parameter
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $name, $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Set Parameter
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParam(string $name, $value): void
    {
        $this->params[$name] = $value;
    }

    /**
     * Set Parameters
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Check if parameter exists
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return self::findParam($this->params, explode('.', $name), null) !== null;
    }

    /**
     * Get Parameter, support dot notation
     *
     * @param string $name
     * @param mixed $default
     * @param bool $explicit If true, only search in the query parameters ($_GET), otherwise search in $_REQUEST and raw body JSON
     * @return mixed
     */
    public function get(string $name, $default = null, bool $explicit = false): mixed
    {
        return self::findParam($explicit ? $_GET : $this->params, explode('.', $name), $default);
    }

    /**
     * Post Parameter, support dot notation
     *
     * @param string $name
     * @param mixed $default
     * @param bool $explicit If true, only search in the form parameters ($_POST), otherwise search in both $_POST and raw body JSON
     * @return mixed
     */
    public function post(string $name, $default = null, bool $explicit = false): mixed
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
    public function cookie(string $name, $default = null): mixed
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
    protected function findParam(array $array, array $path, $default)
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
    public function uri(): string
    {
        return strtok($_SERVER['REQUEST_URI'], '?');
    }

    /**
     * Get requested method
     *
     * @return string
     */
    public function method(): string
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
    public function body(): string
    {
        return file_get_contents('php://input');
    }

    /**
     * @see json_decode()
     * 
     * Get JSON body
     *
     * @param bool $associative
     * @param int $depth
     * @param int $options
     * @return array
     */
    public function json(bool $associative = true, int $depth = 512, int $options = 0): array
    {
        $decoded = json_decode(self::body(), $associative, $depth, $options);
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
    public function files(): array
    {
        return $_FILES;
    }

    /**
     * Get file
     *
     * @param string $name
     * @return array
     */
    public function file($name): array
    {
        return $_FILES[$name] ?? [];
    }

    /**
     * Get HTTP headers
     *
     * @return array
     */
    public function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get client IP
     *
     * @return string
     */
    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Get HTTP header
     *
     * @param string $name
     * @return string|null
     */
    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) == strtolower($name)) {
                return $value;
            }
        }
        return null;
    }

    /** 
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get session
     *
     * @return Session
     */
    public function session(): Session
    {
        return $this->session;
    }

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->header('Content-Type') === 'application/json';
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
