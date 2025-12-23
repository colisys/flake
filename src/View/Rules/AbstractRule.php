<?php

namespace Flake\View\Rules;

use Flake\View\Renderer;

interface Rule
{
    public static function test(string $content): bool;

    public static function apply($content, Renderer $context);
}


abstract class AbstractRule implements Rule
{
    public static string $leftMark;

    public static string $rightMark;

    public static bool $selfClosing = false;

    public static bool $fullContext = false;
}
