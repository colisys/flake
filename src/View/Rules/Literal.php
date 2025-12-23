<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

/**
 * Literal rule
 * 
 * This will process literal values, like {{ time }}, always working.
 */
#[Rule]
class Literal extends AbstractRule
{
    public static string $leftMark = "{{";
    public static string $rightMark = "}}";
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return true;
    }

    public static function apply($content, Renderer $context)
    {
        $data = $context->getData();

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]*?)\s*\}\}/',
            function ($matches) use ($data) {
                $key = $matches[1];
                return $data[$key] ?? '';
            },
            $content
        );
    }
}
