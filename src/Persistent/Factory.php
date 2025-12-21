<?php

namespace Flake\Persistent;

use Flake\Persistent\Builder\SqliteBuilder;
use Flake\Persistent\Driver\SqliteDriver;
use Flake\Persistent\Facade\SqliteFacade;

class Factory
{
    public static function make(string $type, array $options = [])
    {
        switch ($type) {
            case 'sqlite':
                return make(SqliteFacade::class, [
                    'builder' => make(SqliteBuilder::class),
                    'driver' => make(SqliteDriver::class, ['options' => $options])
                ]);

            default:
                throw new DatabaseTypeNotSupportException("Database type '$type' is not supported");
        }
    }
}
