<?php

namespace Flake\Persistent;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;

abstract class Model
{
    protected AbstractBuilder $builder;
    protected AbstractDriver $driver;

    protected string $table = '';
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

    public function getPkName(): string
    {
        return $this->pk;
    }

    public function getPkValue(): int|string|null
    {
        if (array_key_exists($this->pk, $this->changed))
            return $this->changed[$this->pk];
        else
            return $this->data[$this->pk] ?? null;
    }

    public function find(?int $id = null): ?static
    {
        $this->builder
            ->table($this->table)
            ->where($this->pk, $id ?? $this->getPkValue())
            ->limit(1)
            ->select($this->visible);

        $this->driver->connect();
        foreach ($this->driver->query(...$this->builder->build()) as $value) {
            return $this->fill($value);
        }
        return null;
    }

    public function refresh(): ?static
    {
        $name = $this->getPkName();
        if (array_key_exists($name, $this->data) || array_key_exists($name, $this->changed)) {
            $this->changed = [];
            $this->find();
        }

        return $this;
    }

    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable))
                $this->changed[$key] = $value;
        }
        return $this;
    }

    public function save(): static
    {
        if ($this->getPkValue() === null)
            $this->builder
                ->table($this->table)
                ->insert($this->changed);
        else $this->builder
            ->table($this->table)
            ->where($this->getPkName(), $this->getPkValue())
            ->update($this->changed);

        $this->driver->connect();
        $this->driver->execute(...$this->builder->build());
        return $this->refresh();
    }

    public function insert(): static
    {
        $data = array_merge($this->data, $this->changed);
        unset($data[$this->getPkName()]);

        $this->builder
            ->table($this->table)
            ->insert($data);

        $this->driver->connect();
        $this->driver->execute(...$this->builder->build());
        return $this->refresh();
    }

    public function delete(): bool
    {
        $this->builder
            ->table($this->table)
            ->where($this->pk, '=', $this->getPkValue())
            ->delete();

        $this->driver->connect();
        return $this->driver->execute(...$this->builder->build());
    }

    public function toArray(): array
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
