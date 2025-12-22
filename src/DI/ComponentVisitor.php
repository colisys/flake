<?php

namespace Flake\DI;

use Flake\Attributes\Component;
use Flake\DI\Contract\AutoRegister;

use function Flake\dd;

class ComponentVisitor
{
    /**
     * @param class-string $class
     * @return ?array
     * `s` - class name
     * `a` - attributes
     * `p` - properties
     * `m` - methods
     * `c` - constructor
     * `l` - auto register
     * `i` - interfaces
     * `t` - singleton
     */
    public static function visit(string $class)
    {
        if (class_exists($class)) {
            $rclass = new \ReflectionClass($class);
            $attributes = $rclass->getAttributes(Component::class);
            $attrs = array_map(fn($a) => $a->getName(), $attributes);
            $is_component = in_array(Component::class, $attrs);
            $components = array_shift($attributes)?->getArguments() ?? [];

            return [
                's' => $class,
                'a' => $attrs,
                'p' => array_map(fn($p) => [
                    'name' => $p->getName(),
                    'type' => $p->getType()?->getName(),
                    'default' => $p->getDefaultValue(),
                ], $rclass->getProperties()),
                'm' => array_map(fn($m) => $m->getName(), $rclass->getMethods()),
                'c' => $rclass->getConstructor() !== null,
                'l' => $is_component && $rclass->implementsInterface(AutoRegister::class) && ($components['auto_register'] ?? true),
                'i' => array_map(fn($m) => $m->getName(), $rclass->getInterfaces()) ?? [],
                't' => $is_component && ($components['singleton'] ?? true),
            ];
        }
        return null;
    }
}
