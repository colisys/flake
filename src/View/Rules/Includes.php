<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;
use Flake\View\Rules\Exception\RuleMismatchException;

use function Flake\dd;
use function Flake\View\view;

/**
 * Include other view
 * 
 * format A:
 * {{@ path/to/view }}
 * 
 * This will read target view file and render it, then insert rendered content into current view.
 * 
 * format B:
 * {{@ path/to/view noxss}}
 * 
 * This will read target view file and render it, then insert rendered content into current view.
 * But this will not escape XSS. This is useful for including raw HTML.
 */
#[Rule(weight: PHP_INT_MAX)]
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
            '/{{@\s*([^}]+)\s*}}/',
            function ($matches) use ($context) {
                $cond = explode(' ', $matches[1]);
                $path = '';
                $noxss = false;

                if (count($cond) > 0)
                    $path = $cond[0];

                if (count($cond) > 1)
                    $noxss = $cond[1] == 'noxss';

                if ($path === '')
                    throw new RuleMismatchException('Cannot find include path');

                return view($path, $context->getData())
                    ->render(true, $noxss);
            },
            $content
        );
    }
}
