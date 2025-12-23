<?php

namespace Flake\Persistent;

use Flake\Attributes\Component;
use Flake\DI\Contract\AutoRegister;
use Flake\Persistent\Builder\SqliteBuilder;
use Flake\Persistent\Driver\SqliteDriver;
use Flake\Persistent\Exception\DatabaseTypeNotSupportException;
use Flake\Persistent\Facade\AbstractFacade;
use Flake\Persistent\Facade\SqliteFacade;
use Psr\Container\ContainerInterface;

use function Flake\config;
use function Flake\make;

#[Component()]
class Factory implements AutoRegister
{
    /**
     * @param array{"type":string,"database":string} $options
     */
    public function __construct(
        protected ContainerInterface $container,
        array $options = []
    ) {

        $facade = null;
        switch ($options['driver']) {
            case 'sqlite':
                $facade = Factory::make(
                    'sqlite',
                    ['database' => $options['database']]
                );
                break;
        }

        if (method_exists($container, 'set'))
            $container->{"set"}(AbstractFacade::class, $facade);
    }

    public static function make(string $type, array $options = []): AbstractFacade
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

    public static function onAutoRegiste(ContainerInterface $container): void
    {
        if (method_exists($container, 'set'))
            $container->{"set"}(Factory::class, new static($container, config('database', [])));
    }
}
