<?php

namespace Flake\Event;

use Exception;
use Flake\DI\Attributes\Component;
use Flake\DI\ComponentCollector;
use Flake\DI\Contract\AutoRegisterClass;
use Flake\Event\Attribute\EventHandler;
use Flake\Event\Attribute\EventListener;
use Flake\Event\Contract\AbstractListener;
use Flake\Event\Exception\EventDispatchException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

use function Flake\dd;
use function Flake\make;

#[Component(EventDispatcherInterface::class)]
class Dispatcher extends AutoRegisterClass
{
    /**
     * @var array<string,array<int,callable|array{0:class-string,1:string}[]}>
     */
    protected array $listeners = [];

    public function __construct(
        protected ContainerInterface $container
    ) {}

    /**
     * @param object|class-string $event
     * @param callable|array{0:class-string,1: string} $listener
     * @param int $priority
     * @return static
     */
    public function addEventListener($event, $listener, $priority = 0): static
    {
        if (is_object($event)) {
            $event = get_class($event);
        }

        $this->listeners[$event][] = [$priority, $listener];
        usort($this->listeners[$event], fn($a, $b) => $a[0] <=> $b[0]);
        return $this;
    }

    /**
     * @param object|class-string $event
     * @param callable|array{0:class-string,1: string} $listener
     * @return static
     */
    public function removeEventListener($event, $listener): static
    {
        if (is_object($event)) {
            $event = get_class($event);
        }

        foreach ($this->listeners[$event] as $key => $value) {
            if ($value[1] === $listener) {
                unset($this->listeners[$event][$key]);
            }
        }
        return $this;
    }

    protected function hasEvent($event): bool
    {
        if (is_object($event))
            $event = get_class($event);

        return isset($this->listeners[$event]);
    }

    protected function hasListener($event): bool
    {
        return $this->hasEvent($event) &&
            count($this->listeners[is_object($event) ? get_class($event) : $event]) > 0;
    }

    /**
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners[get_class($event)] as $listener) {
            yield $listener[1];
        }
    }

    public function dispatch(object $event)
    {
        if (!$this->hasListener($event))
            return;

        $rclass = new \ReflectionClass($event);
        $stoppable = false;
        if ($rclass->implementsInterface(StoppableEventInterface::class)) {
            $stoppable = true;
        }

        foreach ($this->getListenersForEvent($event) as $dispatcher) {
            try {
                if (is_callable($dispatcher))
                    $dispatcher($event);
                if (is_array($dispatcher))
                    make($dispatcher[0])->{$dispatcher[1]}($event);

                if ($stoppable && $event->isPropagationStopped())
                    break;
            } catch (\Throwable $th) {
                throw new EventDispatchException("Error on dispatching event", $th->getCode(), $th);
            }
        }
    }

    public function onAfterAutoRegiste(ContainerInterface $container): void
    {
        $classes = ComponentCollector::getClassesByAttribute(EventListener::class);
        foreach ($classes as $class => $rclass) {
            if ($rclass->hasMethod("handle") && $rclass->hasProperty("events")) {
                $events = $rclass->getProperty("events")->getValue(make($class));
                foreach ($events as $ev)
                    $this->addEventListener($ev, [$class, "handle"]);
            } else {
                $methods = $rclass->getMethods();
                foreach ($methods as $method) {
                    if ($attrs = $method->getAttributes(EventHandler::class)) {
                        /** @var EventHandler */
                        $attr = $attrs[0]->newInstance();
                        $this->addEventListener($attr->event, [$class, $method->getName()]);
                    }
                }
            }
        }
    }
}
