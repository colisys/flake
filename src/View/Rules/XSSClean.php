<?php

namespace Flake\View\Rules;

use Flake\View\Attribute\Rule;
use Flake\View\Renderer;

use function Flake\dd;

/**
 * XSSClean rule
 * 
 * This will check for XSS contents in the view, and remove them,
 * but be careful, this will not work for all cases.
 */
#[Rule(execution: 'post', weight: 100)]
class XSSClean extends AbstractRule
{
    public static bool $fullContext = true;

    public static function test(string $content): bool
    {
        return true;
    }

    public static function apply($content, Renderer $context)
    {
        $pattern = '/(<noxss>)(.*?)(<\/noxss>)/s';

        preg_replace_callback($pattern, fn($matches) => $matches[2], $content);

        $placeholder_pattern = '/<noxss>.*?<\/noxss>/s';
        $placeholders = [];

        $processed_content = preg_replace_callback(
            $placeholder_pattern,
            function ($matches) use (&$placeholders) {
                $index = count($placeholders);
                $placeholder = "NOXSS_PLACEHOLDER_{$index}_NOXSS";
                $placeholders[$placeholder] = $matches[0];
                return $placeholder;
            },
            $content
        );
        $cleaned_content = xss_clean($processed_content, false);

        foreach ($placeholders as $placeholder => $original) {
            $inner_content_pattern = '/<noxss>(.*?)<\/noxss>/s';
            if (preg_match($inner_content_pattern, $original, $inner_matches)) {
                $inner_content = $inner_matches[1];
                $cleaned_content = str_replace($placeholder, $inner_content, $cleaned_content);
            }
        }

        return $cleaned_content;
    }
}

/**
 * Helper functions
 * 
 * These are helper functions for the XSSClean rule.
 * 
 * From CodeIgniter 3
 */
function remove_invisible_characters($str, $url_encoded = TRUE)
{
    $non_displayables = array();
    if ($url_encoded) {
        $non_displayables[] = '/%0[0-8bcef]/';
        $non_displayables[] = '/%1[0-9a-f]/';
    }
    $non_displayables[] = '/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]+/S';
    do {
        $str = preg_replace($non_displayables, '', $str, -1, $count);
    } while ($count);
    return $str;
}

function _convert_attribute($match)
{
    return str_replace(array('>', '<', '\\'), array('>', '<', '\\\\'), $match[0]);
}

function _decode_entity($match)
{
    $str = $match[0];

    if (stristr($str, '&') === FALSE) {
        return $str;
    }
    $str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
    $str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
    return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
}

function _compact_exploded_words($matches)
{
    return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
}

function _filter_attributes($str)
{
    $out = '';

    if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
        foreach ($matches[0] as $match) {
            $out .= preg_replace("#/\*.*?\*/#s", '', $match);
        }
    }

    return $out;
}

function _js_link_removal($match)
{
    return str_replace(
        $match[1],
        preg_replace(
            '#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
            '',
            _filter_attributes(str_replace(array('<', '>'), '', $match[1]))
        ),
        $match[0]
    );
}

function _js_img_removal($match)
{
    return str_replace(
        $match[1],
        preg_replace(
            '#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
            '',
            _filter_attributes(str_replace(array('<', '>'), '', $match[1]))
        ),
        $match[0]
    );
}

function _sanitize_naughty_html($matches)
{
    // encode opening brace
    $str = '<' . $matches[1] . $matches[2] . $matches[3];

    // encode captured opening or closing brace to prevent recursive vectors
    $str .= str_replace(
        array('>', '<'),
        array('>', '<'),
        $matches[4]
    );

    return $str;
}

function xss_clean($str, $is_image = FALSE)
{
    /*
     * Is the string an array?
     *
     */
    if (is_array($str)) {
        foreach ($str as $key) {
            $str[$key] = xss_clean($str[$key]);
        }

        return $str;
    }

    $str = remove_invisible_characters($str);

    // Validate Entities in URLs
    $hash = md5(time() + mt_rand(0, 1999999999));

    $str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $hash . "\\1=\\2", $str);
    $str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);
    $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str);
    $str = str_replace($hash, '&', $str);

    $str = rawurldecode($str);

    /*
     * Convert character entities to ASCII
     *
     * This permits our tests below to work reliably.
     * We only convert entities that are within tags since
     * these are the ones that will pose security problems.
     *
     */

    $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si",  fn(...$args) => _convert_attribute(...$args), $str);
    $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si",  fn(...$args) => _decode_entity(...$args), $str);

    /*
     * Remove Invisible Characters Again!
     */
    $str = remove_invisible_characters($str);

    /*
     * Convert all tabs to spaces
     *
     * This prevents strings like this: ja  vascript
     * NOTE: we deal with spaces between characters later.
     * NOTE: preg_replace was found to be amazingly slow here on
     * large blocks of data, so we use str_replace.
     */

    if (strpos($str, "\t") !== FALSE) {
        $str = str_replace("\t", ' ', $str);
    }

    /*
     * Capture converted string for later comparison
     */
    $converted_string = $str;

    // Remove Strings that are never allowed
    $_never_allowed_str = array(
        'document.cookie'   => '',
        'document.write'    => '',
        '.parentNode'       => '',
        '.innerHTML'        => '',
        'window.location'   => '',
        '-moz-binding'      => '',
        '<!--'               => '<!--',
        '-->'                => '-->',
        '<![CDATA['          => '<![CDATA[',
        '<comment>'           => '<comment>'
    );
    $str = str_replace(array_keys($_never_allowed_str), $_never_allowed_str, $str);


    $_never_allowed_regex = array(
        'javascript\s*:',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'Redirect\s+302',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );
    foreach ($_never_allowed_regex as $regex) {
        $str = preg_replace('#' . $regex . '#is', '', $str);
    }

    /*
     * Makes PHP tags safe
     *
     * Note: XML tags are inadvertently replaced too:
     *
     * <?xml
     *
     * But it doesn't seem to pose a problem.
     */
    if ($is_image === TRUE) {
        // Images have a tendency to have the PHP short opening and
        // closing tags every so often so we skip those and only
        // do the long opening tags.
        $str = preg_replace('/<\?(php)/i', "<?\\1", $str);
    } else {
        $str = str_replace(array('<?', '?' . '>'),  array('<?', '?>'), $str);
    }

    /*
     * Compact any exploded words
     *
     * This corrects words like:  j a v a s c r i p t
     * These words are compacted back to their correct state.
     */
    $words = array(
        'javascript',
        'expression',
        'vbscript',
        'script',
        'base64',
        'applet',
        'alert',
        'document',
        'write',
        'cookie',
        'window'
    );

    foreach ($words as $word) {
        $temp = '';

        for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++) {
            $temp .= substr($word, $i, 1) . "\s*";
        }

        // We only want to do this when it is followed by a non-word character
        // That way valid stuff like "dealer to" does not become "dealerto"
        $str = preg_replace_callback('#(' . substr($temp, 0, -3) . ')(\W)#is', fn(...$args) => _compact_exploded_words(...$args), $str);
    }

    /*
     * Remove disallowed Javascript in links or img tags
     * We used to do some version comparisons and use of stripos for PHP5,
     * but it is dog slow compared to these simplified non-capturing
     * preg_match(), especially if the pattern exists in the string
     */
    do {
        $original = $str;

        if (preg_match("/<a/i", $str)) {
            $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", fn(...$args) => _js_link_removal(...$args), $str);
        }

        if (preg_match("/<img/i", $str)) {
            $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", fn(...$args) => _js_img_removal(...$args), $str);
        }

        if (preg_match("/script/i", $str) or preg_match("/xss/i", $str)) {
            $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '', $str);
        }
    } while ($original != $str);

    unset($original);

    // Remove evil attributes such as style, onclick and xmlns

    // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
    $evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');

    if ($is_image === TRUE) {
        /*
         * Adobe Photoshop puts XML metadata into JFIF images,
         * including namespacing, so we have to allow this for images.
         */
        unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
    }

    do {
        $count = 0;
        $attribs = array();

        // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
        preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

        foreach ($matches as $attr) {
            $attribs[] = preg_quote($attr[0], '/');
        }

        // find occurrences of illegal attribute strings without quotes
        preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

        foreach ($matches as $attr) {
            $attribs[] = preg_quote($attr[0], '/');
        }

        // replace illegal attribute strings that are inside an html tag
        if (count($attribs) > 0) {
            $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(' . implode('|', $attribs) . ')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
        }
    } while ($count);



    /*
     * Sanitize naughty HTML elements
     *
     * If a tag containing any of the words in the list
     * below is found, the tag gets converted to entities.
     *
     * So this: <blink>
     * Becomes: <blink>
     */
    $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
    $str = preg_replace_callback('#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is', fn(...$args) => _sanitize_naughty_html(...$args), $str);

    /*
     * Sanitize naughty scripting elements
     *
     * Similar to above, only instead of looking for
     * tags it looks for PHP and JavaScript commands
     * that are disallowed.  Rather than removing the
     * code, it simply converts the parenthesis to entities
     * rendering the code un-executable.
     *
     * For example: eval('some code')
     * Becomes:     eval('some code')
     */
    $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2(\\3)", $str);


    // Final clean up
    // This adds a bit of extra precaution in case
    // something got through the above filters
    $str = str_replace(array_keys($_never_allowed_str), $_never_allowed_str, $str);

    foreach ($_never_allowed_regex as $regex) {
        $str = preg_replace('#' . $regex . '#is', '', $str);
    }

    /*
     * Images are Handled in a Special Way
     * - Essentially, we want to know that after all of the character
     * conversion is done whether any unwanted, likely XSS, code was found.
     * If not, we return TRUE, as the image is clean.
     * However, if the string post-conversion does not matched the
     * string post-removal of XSS, then it fails, as there was unwanted XSS
     * code found and removed/changed during processing.
     */

    if ($is_image === TRUE) {
        return ($str == $converted_string) ? TRUE : FALSE;
    }

    return $str;
}
