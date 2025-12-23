<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;
use Flake\View\Rules\Exception\RuleMismatchException;

/**
 * Loop rule
 * 
 * This will process loops, always working.
 * 
 * format A: 
 * [% for $var in $array %}
 *      ...
 * [% endfor %]
 * 
 * format B:
 * [% loop %]
 *      ...
 * [% break %]
 * [% continue %]
 *      ...
 * [% endloop %]
 * 
 * format C:
 * [% do %]
 *      ...
 * [% while $var %]
 * 
 * format D:
 * [% while $var %]
 *      ...
 * [% endwhile %]
 * 
 */
#[Rule]
class Loop extends AbstractRule
{
    public static string $leftMark = "[%";
    public static string $rightMark = "%]";
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return true;
    }

    public static function apply($content, Renderer $context)
    {
        $data = $context->getData();

        $content = preg_replace_callback(
            '/(\[%\s*(for|loop|break|continue|while|endfor|endloop|endwhile|counter(\+|\-)?|endcounter)\s*(.*?)\s*%\])/',
            function ($matches) use ($data) {
                switch ($matches[2]) {
                    case 'for':
                        $matches[3] = explode('in', $matches[3]);
                        if (count($matches[3]) != 2)
                            throw new RuleMismatchException("Invalid for loop, rule: {$matches[2]}, format: {$matches[3]}");
                        return '<?php for(' . $matches[3][0] . ' in ' . $matches[3][1] . '): ?>';
                    case 'loop':
                        return '<?php while(true): ?>';
                    case 'break':
                        return '<?php break; ?>';
                    case 'continue':
                        return '<?php continue; ?>';
                    case 'while':
                        if ($matches[3] == '')
                            throw new RuleMismatchException("Invalid while loop, rule: {$matches[2]}, format: {$matches[3]}");
                        return '<?php while(' . $matches[3] . '): ?>';
                    case 'endfor':
                        return '<?php endfor; ?>';
                    case 'endloop':
                        return '<?php endwhile; ?>';
                    case 'endwhile':
                        return '<?php endwhile; ?>';

                    /**
                     * format E: (The plus sign is used to increment the counter, can be ignored)
                     * 
                     * parts: counter(+|-) target [variable/step]
                     * 
                     * [% counter+ $var inc/1 %]
                     * {{= inc }}
                     *      ...
                     * [% endcounter %]
                     * 
                     * for (int $inc = 0; $inc < $var; $inc+=1):
                     *  echo $inc;
                     * endfor;
                     * 
                     * 
                     * format F: (The minus sign is used to decrement the counter, can not be ignored)
                     * [% counter- $var dec/2 %]
                     * {{= dec }}
                     *      ...
                     * [% endcounter %]
                     * 
                     * for (int $dec = $var; $dec > 0; $dec-=2):
                     *  echo $dec;
                     * endfor;
                     */
                    case 'counter-':
                        $dir = '-=';
                        $cmp = '>';
                        goto c;
                    case 'counter+':
                    case 'counter':
                        $dir = '+=';
                        $cmp = '<';
                        goto c;
                    case 'counter':
                        c:
                        $step = 1;
                        $start = 0;
                        $end = 0;
                        $c = '$i';
                        $cond = explode(' ', $matches[4]);

                        if (count($cond) > 0) {
                            if ($cmp == '>')
                                $start = $cond[0];
                            else
                                $end = $cond[0];
                        }

                        if (count($cond) > 1) {
                            $cond = explode('/', $cond[1]);

                            if (count($cond) > 0) {
                                $c = '$' . $cond[0];
                            }

                            if (count($cond) > 1) {
                                $step = $cond[1];
                            }
                        }

                        return '<?php for(' . $c . '=' . $start . '; ' . $c . $cmp . $end . '; ' . $c . $dir . $step . '): ?>';
                    case 'endcounter':
                        return '<?php endfor; ?>';
                };
            },
            $content
        );

        return $content;
    }
}
