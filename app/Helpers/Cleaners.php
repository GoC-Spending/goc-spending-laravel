<?php


namespace App\Helpers;

class Cleaners
{

    /**
     * For a given unicode value string (e.g. "\u1000"), return the actual character it represents.
     *
     * Thanks to: https://stackoverflow.com/a/6059008/756641
     *
     * @param string        $str       String containing unicode values.
     * @param string|null   $encoding  The encoding to use. Pulls from PHP's setting if left null.
     *
     * @return string  The string with unicode values replaced.
     */
    public static function getStringFromUnicodeValue($str, $encoding = null)
    {
        if (is_null($encoding)) {
            $encoding = ini_get('mbstring.internal_encoding');
        }

        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', function ($match) use ($encoding) {
            return mb_convert_encoding(pack('H*', $match[1]), $encoding, 'UTF-16BE');
        }, $str);
    }

    /**
     * Convert a string representation of a contract's value (e.g. "$ 15,000") to a
     * float version (e.g. "15000").
     *
     * @param string $input  The contract's value field.
     *
     * @return float  The float representation of the contract value.
     */
    public static function cleanContractValue($input)
    {
        // Strip whitespace
        $output = str_replace(' ', '', $input);

        // French currency ends with a dollar sign
        if (substr($output, -1) == '$') {
            // Look for a trailing comman used for demical place
            if (substr($output, -4, 1) == ',') {
              // Fudge in a period for decimal, comma stripped later
                $output = substr_replace($output, '.', -4, 1);
            }
        }

        $output = str_replace(['$', ','], '', $output);

        $output = floatval($output);

        return $output;
    }

    /**
     * Clean an HTML string by converting common entities to their actual forms,
     * and by removing/replacing unicode characters.
     *
     * @param string $value  The HTML value to clean.
     *
     * @return string  The cleaned HTML.
     */
    public static function cleanHtmlValue($value)
    {
        $value = str_replace(['&nbsp;', '&amp;', '&AMP;'], [' ', '&', '&'], $value);

        // Clean up any pesky unicode characters
        // In the case of \u00A0, it's a non-breaking space:
        // Not exactly sure why the Â shows up though...
        $value = str_replace([self::getStringFromUnicodeValue("\u00A0"), ' ', 'Â'], '', $value);

        $value = trim(strip_tags($value));

        return $value;
    }

    /**
     * Clean a contract's label (e.g. "Description:") by removing non-alphanumeric characters.
     *
     * Thanks to: https://stackoverflow.com/a/11321058/756641.
     *
     * @param string $label  The contract label to clean.
     *
     * @return string  The cleaned contract label.
     */
    public static function cleanLabelText($label)
    {
        $label = preg_replace('/[^\da-z]/i', '', strtolower($label));

        $label = trim($label);

        return $label;
    }

    /**
     * Remove linebreaks from a string.
     *
     * @param string $input  The string to clean.
     *
     * @return string  The string without linebreaks.
     */
    public static function removeLinebreaks($input)
    {
        $output = str_replace(["\n", "\r", "\t"], ' ', $input);

        // Remove double spaces:
        $output = str_replace('  ', ' ', $output);

        $output = trim($output);

        return $output;
    }

    /**
     * Clean a string by converting it to UTF-8.
     *
     * @param string $inputText  The text to convert.
     *
     * @return string  The converted text.
     */
    public static function convertToUtf8($inputText)
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $inputText);
    }

    /**
     * Run basic transformations on the raw contract HTML. Meant to be run before any other processing.
     *
     * @param string $html  The source HTML to process.
     *
     * @return string  The source HTML with initial transformations applied.
     */
    public static function applyInitialSourceHtmlTransformations($html)
    {
        // Since <br>'s don't seem to be used in any of the actual table structures, we replace
        // them to avoid text being stuck together when tags are stripped from text content.
        $html = str_replace(['<br>'], [' '], $html);

        return $html;
    }

    /**
     * For departments that use ampersands in link URLs, this converts them to enable retrieving the pages.
     *
     * @param $url string  The URL to clean.
     *
     * @return string  The URL, with encoded ampersands properly converted.
     */
    public static function cleanIncomingUrl($url)
    {
        return str_replace('&amp;', '&', $url);
    }
}
