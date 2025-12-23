<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

use function Flake\dd;

/**
 * Conditions rule
 * 
 * This will process conditions, any valid php conditions will be processed, always working.
 * 
 * format:
 * 
 * {% if $var %}
 *      ...
 * {% elseif $var2 %}
 *      ...
 * {% else %}
 *      ...
 * {% endif %}
 */
#[Rule]
class Conditions extends AbstractRule
{
    public static string $leftMark = '{%';
    public static string $rightMark = '%}';
    public static bool $selfClosing = false;
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return true;
    }

    public static function apply($content, Renderer $context)
    {
        // To simplify, we will use php tags, since we are compiling a view
        // {% -> php tag start
        // %} -> php tag end

        $data = $context->getData();

        $content = preg_replace_callback(
            '/(\{%\s*(if|elseif|else|endif)\s*(.*?)\s*%\})/',
            function ($matches) use ($data) {
                // we dont want anything after 'else' and 'endif'
                if (in_array($matches[2], ['if', 'elseif'])) {
                    return '<?php ' . $matches[2] . '(' . $matches[3] . '): ?>';
                } else if ($matches[2] == 'else') {
                    return '<?php ' . $matches[2] . ': ?>';
                } else {
                    return '<?php ' . $matches[2] . '; ?>';
                }
            },
            $content
        );

        return $content;
    }
}
