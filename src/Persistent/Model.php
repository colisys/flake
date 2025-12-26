<?php

namespace Flake\Persistent;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;
use Flake\Persistent\Event\AfterInsert;
use Flake\Persistent\Event\AfterSave;
use Flake\Persistent\Event\BeforeDelete;
use Flake\Persistent\Event\BeforeInsert;
use Flake\Persistent\Event\BeforeSave;
use Psr\EventDispatcher\EventDispatcherInterface;

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

    protected function getBuilder(): AbstractBuilder
    {
        return $this->builder;
    }

    protected function getDriver(): AbstractDriver
    {
        return $this->driver;
    }

    /**
     * Getter for model properties
     * 
     * This will check if the property is in the visible array, 
     * if not, it will return the property value.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $visibles = static::$visible;
        if (!count($visibles)) $visibles = static::$fields;
        if (in_array($name, $visibles))
            if (array_key_exists($name, $this->data)) {
                if (array_key_exists($name, $this->changed))
                    return $this->changed[$name];
                return $this->data[$name];
            }

        return $this->{$name};
    }

    /**
     * Setter for model properties
     * 
     * This will check if the property is in the fillable array, 
     * if not, it will set the property value.
     * 
     * @param string $name
     * @param mixed $value
     */
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
        if (make(EventDispatcherInterface::class)?->dispatch(new BeforeSave($this)) === false)
            return $this;

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
        $this->refresh();

        make(EventDispatcherInterface::class)?->dispatch(new AfterSave($this));

        return $this;
    }

    public function insert(): static
    {
        if (make(EventDispatcherInterface::class)?->dispatch(new BeforeInsert($this)) === false)
            return $this;

        $data = array_merge($this->data, $this->changed);
        unset($data[$this->getPkName()]);

        $this->builder
            ->table(static::$table)
            ->insert($data);

        $this->driver->connect();
        $this->driver->execute(...$this->builder->build());
        $this->refresh();

        make(EventDispatcherInterface::class)?->dispatch(new AfterInsert($this));

        return $this;
    }

    public function delete(): bool
    {
        if (make(EventDispatcherInterface::class)?->dispatch(new BeforeDelete($this)) === false)
            return true;

        $this->builder
            ->table(static::$table)
            ->where($this->pk, '=', $this->getPkValue())
            ->delete();

        $this->driver->connect();
        return $this->driver->execute(...$this->builder->build());
    }

    /**
     * @param \Closure(AbstractBuilder $builder) $callable
     * @return \Generator<int,static,>
     */
    public static function query($callable)
    {
        $driver = make(AbstractDriver::class);
        $builder = make(AbstractBuilder::class);
        $builder->table(static::$table);
        $callable($builder);

        $driver->connect();
        foreach ($driver->query(...$builder->build()) ?? [] as $value) {
            yield new static($value);
        }
    }

    /**
     * Get the value of a column, this will ignore visiblity and hidden columns.
     * 
     * @param string $column
     * @return mixed
     */
    public function get($column = null)
    {
        if (!isset($column))
            return $this;

        if (is_string($column))
            if (array_key_exists($column, $this->changed))
                return $this->changed[$column];
            else if (array_key_exists($column, $this->data))
                return $this->data[$column];

        if (is_array($column))
            return array_filter($this->toArray(), function ($key) use ($column) {
                return in_array($key, $column);
            }, ARRAY_FILTER_USE_KEY);

        return null;
    }

    public function toArray(): array
    {
        $data = [];
        $visibles = static::$visible;
        if (!count($visibles)) $visibles = static::$fields;
        foreach (array_merge($this->data, $this->changed) as $key => $value) {
            if (in_array($key, $visibles))
                $data[$key] = $value;
            if (in_array($key, static::$hidden))
                unset($data[$key]);
        }
        return $data;
    }
}
