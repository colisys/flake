<?php

namespace Flake\DI;

use Flake\DI\Attributes\Component;
use Flake\DI\Contract\AfterAutoRegister;
use Flake\DI\Contract\AutoRegister;

use function Flake\dd;

class ComponentVisitor
{
    protected static function determineType($type)
    {
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn($t) => self::determineType($t), $type->getTypes()));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(fn($t) => self::determineType($t), $type->getTypes()));
        }

        return gettype($type);
    }

    /**
     * @param class-string $class
     * @return ?array
     * `s` - class name
     * `a` - attributes
     * `p` - properties
     * `m` - methods
     * `c` - constructor
     * `l` - auto register
     * `d` - after auto register
     * `i` - interfaces
     * `t` - singleton
     */
    public static function visit(string $class)
    {
        if (class_exists($class)) {
            $rclass = new \ReflectionClass($class);
            $attributes = $rclass->getAttributes();
            $attrs = array_map(fn($a) => $a->getName(), $attributes);
            $is_component = in_array(Component::class, $attrs);
            /** @var Component */
            $component = $is_component && $attributes[0]?->newInstance();

            return [
                's' => $class,
                'a' => $attrs,
                'p' => array_map(fn($p) => [
                    'name' => $p->getName(),
                    'type' => self::determineType($p->getType()),
                    'default' => $p->getDefaultValue(),
                ], $rclass->getProperties()),
                'm' => array_map(fn($m) => $m->getName(), $rclass->getMethods()),
                'c' => $rclass->getConstructor() !== null,
                'l' => $is_component && $rclass->implementsInterface(AutoRegister::class) && ($component?->auto_register ?? true),
                'd' => $is_component && $rclass->implementsInterface(AfterAutoRegister::class) && ($component?->auto_register ?? true),
                'i' => array_map(fn($m) => $m->getName(), $rclass->getInterfaces()) ?? [],
                't' => $is_component && ($component?->singleton ?? true),
            ];
        }
        return null;
    }
}
