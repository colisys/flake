<?php

namespace Flake\Persistent;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;

abstract class Model
{
    protected AbstractBuilder $builder;
    protected AbstractDriver $driver;

    protected string $pk = 'id';
    protected array $visible = ['*'];
    protected array $hidden = [];
    protected array $fillable = [];
    protected array $casts = [];
    protected array $data = [];
    protected array $changed = [];

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            if (array_key_exists($name, $this->changed))
                return $this->changed[$name];
            return $this->data[$name];
        }
        if (in_array($name, $this->hidden))
            return null;

        return $this->{$name};
    }

    public function __construct(AbstractBuilder $builder, AbstractDriver $driver)
    {
        $this->builder = $builder;
        $this->driver = $driver;
    }

    public function getPkName()
    {
        return $this->pk;
    }

    public function getPkValue()
    {
        if (array_key_exists($this->pk, $this->changed))
            return $this->changed[$this->pk];
        else
            return $this->data[$this->pk] ?? null;
    }

    public function find(?int $id = null)
    {
        $this->builder
            ->where($this->pk, '=', $id ?? $this->getPkValue())
            ->limit(1)
            ->select($this->visible);

        $this->driver->connect();
        foreach ($this->driver->query(...$this->builder->build()) as $value) {
            return $this->fill($value);
        }
        return null;
    }

    public function refresh()
    {
        $this->changed = [];
        $this->find();
    }

    public function fill(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable))
                $this->changed[$key] = $value;
        }
        return $this;
    }

    public function save()
    {
        throw new \Exception('Not implemented');
    }

    public function delete()
    {
        throw new \Exception('Not implemented');
    }

    public function toArray()
    {
        $data = [];
        foreach ($this->data as $key => $value) {
            if (in_array($key, $this->hidden))
                continue;
            if (in_array($key, $this->visible))
                $data[$key] = $value;
        }
        return $data;
    }
}
