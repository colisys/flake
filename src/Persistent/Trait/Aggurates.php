<?php

namespace Flake\Persistent\Trait;

use Flake\Persistent\Builder\AbstractBuilder;
use Flake\Persistent\Driver\AbstractDriver;

use function Flake\make;

trait Aggurates
{
    /**
     * Get the count of rows
     * 
     * @param ?\Closure(AbstractBuilder $builder) $filter
     * @return int
     */
    public static function count(?callable $filter = null): int
    {
        $builder = make(AbstractBuilder::class);
        $driver = make(AbstractDriver::class);

        $builder->table(static::${'table'});
        if (is_callable($filter))
            $filter($builder);
        else $builder->selectRaw('COUNT(*) as __count_rows');

        $driver->connect();
        try {
            if ($result = $driver->fetchOne(...$builder->build())) {
                return $result['__count_rows'];
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return 0;
    }

    /**
     * Get the maximum value of a column
     * 
     * @param string $column
     * @return int
     */
    public static function max(string $column): int
    {
        $builder = make(AbstractBuilder::class);
        $driver = make(AbstractDriver::class);

        $builder->table(static::${'table'});
        $builder->selectRaw("MAX({$column}) as __max");

        $driver->connect();
        try {
            if ($result = $driver->fetchOne(...$builder->build())) {
                return $result['__max'];
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return 0;
    }

    /**
     * Get the minimum value of a column
     * 
     * @param string $column
     * @return int
     */
    public static function min(string $column): int
    {
        $builder = make(AbstractBuilder::class);
        $driver = make(AbstractDriver::class);

        $builder->table(static::${'table'});
        $builder->selectRaw("MIN({$column}) as __min");

        $driver->connect();
        try {
            if ($result = $driver->fetchOne(...$builder->build())) {
                return $result['__min'];
            }
        } catch (\Throwable $th) {
        }
        return 0;
    }

    /**
     * Get the average value of a column
     * 
     * @param string $column
     * @return int
     */
    public static function avg(string $column): int
    {
        $builder = make(AbstractBuilder::class);
        $driver = make(AbstractDriver::class);

        $builder->table(static::${'table'});
        $builder->selectRaw("AVG({$column}) as __avg");

        $driver->connect();
        try {
            if ($result = $driver->fetchOne(...$builder->build())) {
                return $result['__avg'];
            }
        } catch (\Throwable $th) {
        }
        return 0;
    }
}
