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
            '/{{=\s*([^}]+)\s*}}/',
            function ($matches) use ($data) {
                $var  = $matches[1];

                // Split for pipe
                if (strpos($var, '|') !== false) {
                    $parts = explode('|', $var);
                    $var = array_shift($parts);

                    if (strpos($var, '.') !== false) {
                        $dot = explode('.', $var);
                        $var = array_shift($dot);
                        while (count($dot) > 0)
                            $var .= "['" . array_shift($dot) . "']";
                    }

                    while (count($parts) > 0) {
                        $cmd = array_shift($parts);
                        $args = "";

                        if (strpos($cmd, ':') !== false) {
                            $pipe = explode(':', $cmd, 2);
                            $cmd = array_shift($pipe);
                            $args = array_shift($pipe);

                            // Special case, for the "~" command, reverse the arguments
                            if (str_starts_with($cmd, "~")) {
                                $cmd = str_replace("~", "", $cmd);
                                $var = $cmd . "($args, $var)";
                                continue;
                            }
                        }

                        $var = $cmd . '(' . $var . (strlen($args) ? ', ' . $args : '') . ')';
                    }
                } else {
                    if (strpos($var, '.') !== false) {
                        $dot = explode('.', $var);
                        $var = array_shift($dot);
                        while (count($dot) > 0)
                            $var .= "['" . array_shift($dot) . "']";
                    }

                    $var = 'echo (' . $var . ')';
                }

                return '<?php ' . $var . '; ?>';
            },
            $content
        );

        return $content;
    }
}
