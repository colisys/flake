<?php

namespace Flake\Cache;

use Flake\DI\Attributes\Component;
use Flake\Cache\Exception\CacheNotSupportException;
use Flake\Cache\Facade\AbstractFacade;
use Flake\Cache\Facade\FileCacheFacade;
use Flake\DI\Contract\AutoRegister;
use Psr\Container\ContainerInterface;

use function Flake\config;
use function Flake\make;

#[Component()]
class Factory implements AutoRegister
{
    /**
     * @param array{"driver":string,"database":string} $options
     */
    public function __construct(
        protected ContainerInterface $container,
        array $options = []
    ) {

        $facade = null;
        switch ($options['driver'] ?? "") {
            case 'file':
                $facade = Factory::make(
                    'file',
                    ['options' => $options]
                );
                break;
        }

        if (method_exists($container, 'set'))
            $container->{"set"}(AbstractFacade::class, $facade);
    }

    public static function make(string $type, array $options = []): AbstractFacade
    {
        switch ($type) {
            case 'file':
                return make(FileCacheFacade::class, $options);
            default:
                throw new CacheNotSupportException("Cache type $type not support");
        }
    }

    public static function onAutoRegiste(ContainerInterface $container): void
    {
        if (method_exists($container, 'set'))
            $container->{"set"}(Factory::class, new static($container, config('cache', [])));
    }
}
