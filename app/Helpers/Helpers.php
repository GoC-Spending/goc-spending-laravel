<?php
namespace App\Helpers;

use XPathSelector\Selector;

class Helpers
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
        $output = str_replace(['$', ',', ' '], '', $input);

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
     * Check that required contract values are present, adding them if not.
     *
     * @param array $contract    The contract to clean.
     * @param array $vendorData  The data for the contract vendor, if applicable.
     */
    public static function assureRequiredContractValues(&$contract, $vendorData = [])
    {
        // In some cases, entries are missing a contract period start, but do have a contract date. If so, use that instead:
        if (! $contract['startYear']) {
            $contract['startYear'] = Helpers::extractYearFromDate($contract['contractDate']);
        }

        // If there's no end year, assume that it's the same as the start year:
        if (! $contract['endYear']) {
            if ($contract['deliveryDate']) {
                $contract['endYear'] = Helpers::extractYearFromDate($contract['deliveryDate']);
            } else {
                $contract['endYear'] = $contract['startYear'];
            }
        }

        // If there's no original contract value, use the current value:
        if (! $contract['originalValue']) {
            $contract['originalValue'] = $contract['contractValue'];
        }

        $contract['yearsDuration'] = abs($contract['endYear'] - $contract['startYear']) + 1;
        $contract['valuePerYear'] = $contract['contractValue'] / $contract['yearsDuration'];

        // Find the consolidated vendor name:
        if ($vendorData) {
            $contract['vendorClean'] = $vendorData->consolidateVendorNames($contract['vendorName']);
        }
        
        // Remove any linebreaks etc.
        // vendorName
        // referenceNumber
        // description
        // comments
        foreach (['vendorName', 'referenceNumber', 'description', 'comments', 'extraDescription'] as $textField) {
            if (isset($contract[$textField]) && $contract[$textField]) {
                $contract[$textField] = self::removeLinebreaks($contract[$textField]);
            }
        }
    }

    /**
     * Extract a year from a date string.
     *
     * @param string $dateInput  The date.
     *
     * @return bool|string
     */
    public static function extractYearFromDate($dateInput)
    {
        $matches = [];
        $pattern = '/([1-2][0-9][0-9][0-9])/';

        preg_match($pattern, $dateInput, $matches);

        if (! empty($matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Extract a Chart of Accounts Object Code from a contract description.
     *
     * For example:
     *   "514- Rental of other buildings" -> 0514
     *   "1228 - Computer software"       -> 1228
     *
     * The full list of Chart of Accounts Object Codes is available here,
     * https://www.tpsgc-pwgsc.gc.ca/recgen/pceaf-gwcoa/1718/ressource-resource-eng.html
     * as the last link on the page.
     *
     * @param string $description  The contract description.
     *
     * @return string  The object code.
     */
    public static function extractObjectCodeFromDescription($description)
    {
        $objectCode = '';

        $matches = [];
        $pattern = '/([0-9]{3,4})/';

        preg_match($pattern, $description, $matches);

        if ($matches) {
            // Get the matching pattern, and left-pad it with zeroes
            // Sometimes these show up as eg. 514 and sometimes 0514
            $objectCode = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
        }

        return $objectCode;
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

    /**
     * Get a list of the department acronyms stored in the raw data folder.
     *
     * @return string[]  List of department acronyms.
     */
    public static function getDepartments()
    {
        $output = [];
        $sourceDirectory = Paths::getSourceDirectory();

        $departments = array_diff(scandir($sourceDirectory), ['..', '.']);

        // Make sure that these are really directories
        // This could probably done with some more elegant array map function
        foreach ($departments as $department) {
            if (is_dir($sourceDirectory . $department)) {
                $output[] = $department;
            }
        }

        return $output;
    }
}
