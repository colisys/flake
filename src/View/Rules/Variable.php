<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

use function Flake\dd;

/**
 * Variable rule
 * 
 * format A:
 * {{= var }}
 * 
 * <?php echo $var; ?>
 * 
 * format: B
 * {{= var|print_r }}
 * 
 * <?php print_r($var); ?>
 * 
 * format: C:
 * {{= var|print_r|escape }}
 * 
 * <?php print_r(escape($var)); ?>
 */
#[Rule]
class Variable extends AbstractRule
{
    public static string $leftMark = '{{=';
    public static string $rightMark = '}}';
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return true;
    }

    public static function apply($content, Renderer $context)
    {
        $data = $context->getData();

        $content = preg_replace_callback(
            '/{{=\s*([^} ]+)\s*}}/',
            function ($matches) use ($data) {
                $var = $matches[1];

                if (strpos($var, '|') !== false) {
                    $parts = explode('|', $var);
                    if (count($parts) > 0)
                        $var = array_shift($parts);
                    while (count($parts) > 0)
                        $var = array_shift($parts) . '(' . $var . ')';
                } else {
                    $var = 'echo($' . $var . ')';
                }

                return '<?php ' . $var . '; ?>';
            },
            $content
        );

        return $content;
    }
}
