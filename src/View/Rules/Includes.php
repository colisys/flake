<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

use function Flake\dd;
use function Flake\View\view;

/**
 * Include other view
 * 
 * format:
 * {{@ path/to/view }}
 * 
 * This will read target view file and render it, then insert rendered content into current view.
 */
#[Rule]
class Includes extends AbstractRule
{
    public static string $leftMark = '{{@';
    public static string $rightMark = '}}';
    public static bool $fullContext = false;

    public static function test(string $content): bool
    {
        return preg_match('/{{@\s*[^}]+\s*}}/', $content) > 0;
    }

    public static function apply($content, Renderer $context)
    {
        return preg_replace_callback(
            '/{{@\s*([^ ]+)\s*}}/',
            function ($matches) use ($context) {
                return view($matches[1], $context->getData())->render(true);
            },
            $content
        );
    }
}
