<?php

namespace Flake;

use Flake\Contract\MiddlewareInterface;
use Flake\DI\Attributes\Component;
use Flake\DI\ComponentCollector;
use Flake\DI\Contract\AutoRegisterClass;
use Flake\Event\Builtin\MiddlewareExecutionFailedEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Flake\make;

#[Component()]
class Middleware extends AutoRegisterClass
{
    /**
     * @var array<\Closure(Request $request, Response $response, \Closure $next)>
     */
    protected static array $middlewares = [];

    public function __construct(protected ContainerInterface $container) {}

    /**
     * Add a middleware to the stack
     *
     * @param array{0:class-string,1:string}|\Closure(Request &$request, Response &$response, \Closure $next) $middleware
     */
    public static function use($middleware): void
    {
        if (is_callable($middleware)) {
            self::$middlewares[] = $middleware;
            return;
        }

        if (is_array($middleware) && count($middleware) == 2) {
            self::$middlewares[] = ComponentCollector::getMethodByName($middleware[0], $middleware[1])
                ?->getClosure(make($middleware[0])) ?? throw new \Exception("Method not found");
            return;
        }

        throw new \Exception("Middleware must be callable.");
    }

    /**
     * Run the middleware stack
     *
     * @return array{0: Request, 1: Response}
     */
    public static function run(Request $request, Response $response): array
    {
        // Walk through the middleware stack, unless middleware returns null
        $middlewares = self::$middlewares;
        $result = function (Request $request, Response $response) {
            return function () use ($request, $response) {
                return [$request, $response];
            };
        };

        while (count($middlewares) > 0) {
            $middleware = array_shift($middlewares);
            $result = $middleware($request, $response, $result);
            if (is_subclass_of($result, Response::class) || is_a($result, Response::class)) {
                if (!$result->sent)
                    $result->truncate();
                die();
            }

            if ($result == null) {
                make(EventDispatcherInterface::class)?->dispatch(new MiddlewareExecutionFailedEvent($middleware));
                die();
            }
            list($request, $response) = is_callable($result) ? $result() : $result;
        }

        return [$request, $response];
    }

    public function onAfterAutoRegiste(ContainerInterface $container): void
    {
        $classes = ComponentCollector::getClassesByInterface(MiddlewareInterface::class);
        foreach ($classes as $class => $rclass) {
            self::use($rclass->getMethod("handle")->getClosure(make($class)));
        }
    }
}
