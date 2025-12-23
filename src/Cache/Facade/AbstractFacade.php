<?php

namespace Flake\Cache\Facade;

interface AbstractFacade
{
    public function __construct(array $options = []);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, $value, $ttl = 0);

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * @param string $key
     * @return ?array{"version":int,"created":int,"expires":int,"hash":string}
     */
    public function getInfo(string $key);

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key);

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key);
}
