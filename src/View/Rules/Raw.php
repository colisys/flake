<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

use function Flake\dd;

#[Rule()]
class Raw extends AbstractRule
{
    public static string $leftMark = "{{!";
    public static string $rightMark = "!}}";
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return preg_match('#' . self::$leftMark . '.*' . self::$rightMark . '#', $content) !== false;
    }

    public static function apply($content, Renderer $context)
    {
        $content = preg_replace_callback(
            '#' . self::$leftMark . '(.*?)' . self::$rightMark . '#',
            function ($matches) {
                return '<noxss>' . $matches[1] . '</noxss>';
            },
            $content
        );

        return $content;
    }
}
