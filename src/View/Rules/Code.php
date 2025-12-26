<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

use function Flake\dd;

#[Rule()]
class Code extends AbstractRule
{
    public static string $leftMark = '{{:';
    public static string $rightMark = ':}}';
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return preg_match('#' . self::$leftMark . '.*' . self::$rightMark . '#', $content) !== false;
    }

    public static function apply($content, Renderer $context)
    {
        return preg_replace_callback(
            '#' . self::$leftMark . '([a-zA-Z0-9]+)?\s*(.*?)' . self::$rightMark . '#',
            function ($matches) use ($context) {
                return '<code language="' . ($matches[1] ?? 'plain') . '"><pre><noxss>' . htmlspecialchars($matches[2]) . '</noxss></pre></code>';
            },
            $content
        );
    }
}
