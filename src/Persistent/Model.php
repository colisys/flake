<?php

namespace Flake\Persistent;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;

use function Flake\dd;
use function Flake\make;

abstract class Model
{
    protected AbstractBuilder $builder;
    protected AbstractDriver $driver;

    public static string $table = '';
    public static string $pk = 'id';
    public static array $fields = ['*'];
    public static array $visible = ['*'];
    public static array $hidden = [];
    public static array $fillable = [];
    public static array $casts = [];

    protected array $data = [];
    protected array $changed = [];

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            if (array_key_exists($name, $this->changed))
                return $this->changed[$name];
            return $this->data[$name];
        }
        if (in_array($name, static::$hidden))
            return null;

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        if (in_array($name, static::$fillable)) {
            $this->changed[$name] = $value;
            return;
        }

        $this->{$name} = $value;
    }

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->builder = make(AbstractBuilder::class);
        $this->driver = make(AbstractDriver::class);
    }

    public static function getPkName(): string
    {
        return static::$pk;
    }

    public function getPkValue(): int|string|null
    {
        if (array_key_exists(static::getPkName(), $this->changed))
            return $this->changed[static::getPkName()];
        else
            return $this->data[static::getPkName()] ?? null;
    }

    /**
     * @param string|int|null $id
     */
    public static function find($id = null): ?static
    {
        $builder = make(AbstractBuilder::class);
        $builder->table(static::$table)
            ->where(static::getPkName(), $id)
            ->limit(1)
            ->select(static::$fields);

        $driver = make(AbstractDriver::class);

        $driver->connect();
        foreach ($driver->query(...$builder->build()) as $value) {
            return new static($value);
        }
        return null;
    }

    public function refresh(): ?static
    {
        $name = static::getPkName();
        if (array_key_exists($name, $this->data) || array_key_exists($name, $this->changed)) {
            $this->changed = [];
            $data = static::find($this->getPkValue())?->toArray() ?? [];
            foreach (static::$fillable as $index => $field) {
                $this->data[$field] = $data[$field] ?? null;
            }
        }

        return $this;
    }

    public function fill(array $data = []): static
    {
        foreach ($data as $key => $value) {
            if (in_array($key, static::$fillable))
                $this->changed[$key] = $value;
        }
        return $this;
    }

    public function save(): static
    {
        if ($this->getPkValue() === null)
            $this->builder
                ->table(static::$table)
                ->insert(array_merge($this->data, $this->changed));
        else $this->builder
            ->table(static::$table)
            ->where(static::getPkName(), $this->getPkValue())
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
            ->table(static::$table)
            ->insert($data);

        $this->driver->connect();
        $this->driver->execute(...$this->builder->build());
        return $this->refresh();
    }

    public function delete(): bool
    {
        $this->builder
            ->table(static::$table)
            ->where($this->pk, '=', $this->getPkValue())
            ->delete();

        $this->driver->connect();
        return $this->driver->execute(...$this->builder->build());
    }

    public function toArray(): array
    {
        $data = [];
        foreach (array_merge($this->data, $this->changed) as $key => $value) {
            if (in_array($key, static::$fields) || in_array($key, static::$visible))
                $data[$key] = $value;
            if (in_array($key, static::$hidden))
                unset($data[$key]);
        }
        return $data;
    }
}
