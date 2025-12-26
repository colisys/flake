<?php

namespace Flake\Persistent;

use Flake\DI\Attributes\Component;
use Flake\DI\Contract\AutoRegister;
use Flake\Middleware;
use Flake\Persistent\Attribute\ModelMapping;
use Flake\Persistent\Builder\SqliteBuilder;
use Flake\Persistent\Driver\SqliteDriver;
use Flake\Persistent\Exception\DatabaseTypeNotSupportException;
use Flake\Persistent\Facade\AbstractFacade;
use Flake\Persistent\Facade\SqliteFacade;
use Flake\Router;
use Psr\Container\ContainerInterface;

use function Flake\config;
use function Flake\dd;
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
        Middleware::use(function (&$request, &$response, $next) {
            $router = make(Router::class)::buildRouter();
            if (is_array($router)) {
                [$class, $method] = $router;
                $rmethod = new \ReflectionMethod($class, $method);
            }
            if (is_callable($router)) {
                $rmethod = new \ReflectionFunction($router);
            }

            foreach ($rmethod->getParameters() as $param) {
                if (is_subclass_of($param->getType()?->getName(), Model::class, true)) {
                    if ($instance = $param->getAttributes(ModelMapping::class)[0]?->newInstance()) {
                        $pkId = $instance->pk;
                        $paramName = $param->getName();
                        if (str_starts_with($pkId, ">")) {
                            $query = substr($pkId, 1);
                            $pkId = $request->get($query, $instance->defaults);
                        }

                        if ($param->getType() instanceof \ReflectionNamedType) {
                            $rmethod = new \ReflectionMethod($param->getType()->getName(), "find");
                            $instance = $rmethod->invoke(null, $pkId);
                            $request->setParam($paramName, $instance);
                        }
                    }
                }
            }

            return $next($request, $response);
        });

        if (method_exists($container, 'set'))
            $container->{"set"}(Factory::class, new static($container, config('database', [])));
    }
}
