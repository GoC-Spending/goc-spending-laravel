<?php
namespace App\Helpers;

use XPathSelector\Selector;

class Helpers
{

    // Thanks to
    // https://stackoverflow.com/a/6059008/756641
    // Usage
    // $str = unicodeString("\u1000");
    public static function unicodeString($str, $encoding = null)
    {
        if (is_null($encoding)) {
            $encoding = ini_get('mbstring.internal_encoding');
        }
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', function ($match) use ($encoding) {
            return mb_convert_encoding(pack('H*', $match[1]), $encoding, 'UTF-16BE');
        }, $str);
    }

    public static function cleanContractValue($input)
    {

        $output = str_replace(['$', ',', ' '], '', $input);
        return floatval($output);
    }

    public static function cleanHtmlValue($value)
    {

        $value = str_replace(['&nbsp;', '&amp;', '&AMP;'], [' ', '&', '&'], $value);
        
        // Clean up any pesky unicode characters
        // In the case of \u00A0, it's a non-breaking space:
        // Not exactly sure why the Â shows up though...
        $value = str_replace([self::unicodeString("\u00A0"), ' ', 'Â'], '', $value);

        $value = trim(strip_tags($value));
        return $value;
    }

    public static function cleanLabelText($label)
    {

        // Thanks to
        // https://stackoverflow.com/a/11321058/756641
        $label = preg_replace('/[^\da-z]/i', '', strtolower($label));

        return trim($label);
    }

    public static function removeLinebreaks($input)
    {

        $output = str_replace(["\n", "\r", "\t"], ' ', $input);
        return trim($output);
    }

    public static function cleanText($inputText)
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $inputText);
    }

    // $periodSplitString = " to "
    public static function genericXpathParser($html, $keyXpath, $valueXpath, $periodSplitString, $keyArray = [])
    {

        $values = [];
        $defaultKeyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => 'Description (more details):',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value:',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        if ($keyArray == []) {
            $keyArray = $defaultKeyArray;
        }

        $cleanKeys = [];
        foreach ($keyArray as $key => $label) {
            $cleanKeys[$key] = Helpers::cleanLabelText($label);
        }

        $labelToKey = array_flip($cleanKeys);

        $xs = Selector::loadHTML($html);

        // Extracts the keys (from the <th> tags) in order
        $keyNodes = $xs->findAll($keyXpath)->map(function ($node, $index) {
            return (string)$node;
        });

        // Extracts the values (from the <td> tags) in hopefully the same order:
        $valueNodes = $xs->findAll($valueXpath)->map(function ($node, $index) {
            return (string)$node;
        });

        $keys = [];

        // var_dump($keyNodes);
        // var_dump($valueNodes);

        foreach ($keyNodes as $index => $keyNode) {
            $keyNode = Helpers::cleanLabelText($keyNode);

            if (isset($labelToKey[$keyNode]) && $labelToKey[$keyNode] && isset($valueNodes[$index])) {
                $values[$labelToKey[$keyNode]] = Helpers::cleanHtmlValue($valueNodes[$index]);
            }
        }

        // var_dump($values);
        // exit();

        // Change the "to" range into start and end values:
        if (isset($values['contractPeriodRange']) && $values['contractPeriodRange']) {
            $split = explode($periodSplitString, $values['contractPeriodRange']);

            if (isset($split[0]) && isset($split[1])) {
                $values['contractPeriodStart'] = trim($split[0]);
                $values['contractPeriodEnd'] = trim($split[1]);
            }
        }

        // var_dump($values);
        return $values;
    }

    public static function xpathRegexComboSearch($html, $xpathQuery, $regexPattern = '')
    {

        $output = '';

        $xs = Selector::loadHTML($html);
        $text = $xs->find($xpathQuery)->innerHTML();

        if (! $regexPattern) {
            return $text;
        }

        $matches = [];
        $pattern = $regexPattern;

        preg_match($pattern, $text, $matches);
        if ($matches) {
            $output = $matches[1];
        }

        return $output;
    }

    // Just in case there are any changes we want to make to all contract HTML files before they're run through the parser script:
    public static function initialSourceTransform($html, $acronym)
    {
        
        // Since <br>'s don't seem to be used in any of the actual table structures, this way we can avoid text being stuck together when tags are stripped from text content:
        $html = str_replace(['<br>'], [' '], $html);

        return $html;
    }


    // Contract clean-up of missing values etc.:
    public static function checkContractValues(&$contract, $vendorData = [])
    {

        // In some cases, entries are missing a contract period start, but do have a contract date. If so, use that instead:
        if (! $contract['startYear']) {
            // if($contract['deliveryDate']) {
            // 	$contract['startYear'] = Helpers::yearFromDate($contract['deliveryDate']);
            // }
            // else {
            // 	$contract['startYear'] = Helpers::yearFromDate($contract['contractDate']);
            // }

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

        // For example,
        // 514- Rental of other buildings
        // 1228 - Computer software

        // The full list of Chart of Accounts Object Codes is available here,
        // https://www.tpsgc-pwgsc.gc.ca/recgen/pceaf-gwcoa/1718/ressource-resource-eng.html
        // as the last link on the page.

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
     * Pull an array of items, selected via an XPath selector, from an
     * HTML page.
     *
     * @param $htmlSource string  The HTML to run the XPath selector on.
     * @param $xpath      string  The XPath selector to extract the items.
     *
     * @return string[]  The items converted to strings, stored in an array, and deduped.
     */
    public static function arrayFromHtmlViaXpath($htmlSource, $xpath)
    {

        $xs = Selector::loadHTML($htmlSource);

        $items = $xs->findAll($xpath)->map(function ($node, $index) {
            return (string)$node;
        });

        return array_unique($items);
    }

    /**
     * Generate a unique filename based on a contract URL.
     *
     * Note: The contract URL should be a "permalink" version. Usually
     *       this means you’ll need to remove any session information
     *       or other data that isn’t directly related to that contract.
     *
     * @param string $url        The URL for the contract.
     * @param string $extension  The extension to use for the filename.
     *
     * @return string  The filename based on the contract information.
     */
    public static function generateUrlFromFilename($url, $extension = '.html')
    {
        return md5($url) . $extension;
    }

    /**
     * For departments that use ampersands in link URLs, this converts them to enable retrieving the pages.
     *
     * @param $url string  The URL to clean.
     *
     * @return string  The URL, with encoded ampersands properly converted.
     */
    public static function cleanupIncomingUrl($url)
    {
        return str_replace('&amp;', '&', $url);
    }

    /**
     * Get the path to the raw data folder.
     *
     * @return string  The path to the raw data folder, with a trailing slash.
     */
    public static function getSourceDirectory()
    {
        return storage_path() . '/' . env('FETCH_RAW_HTML_FOLDER', 'raw-data') . '/';
    }

    /**
     * Get the path to the raw data folder for a given department.
     *
     * @param $acronym string  The department's acronym.
     *
     * @return string  The path to the department's raw data folder.
     */
    public static function getSourceDirectoryForDepartment($acronym)
    {
        return Helpers::getSourceDirectory() . $acronym;
    }

    /**
     * Get the path to the metadata folder.
     *
     * @return string  The path to the metadata folder, with a trailing slash.
     */
    public static function getMetadataDirectory()
    {
        return storage_path() . '/' . env('FETCH_METADATA_FOLDER', 'metadata') . '/';
    }

    /**
     * Get the path to the metadata folder for a given department.
     *
     * @param $acronym string  The department's acronym.
     *
     * @return string  The path to the department's metadata folder.
     */
    public static function getMetadataDirectoryForDepartment($acronym)
    {
        return Helpers::getMetadataDirectory() . $acronym;
    }

    /**
     * Get a list of the department acronyms stored in the raw data folder.
     *
     * @return array  List of department acronyms.
     */
    public static function getDepartments()
    {
        $output = [];
        $sourceDirectory = Helpers::getSourceDirectory();

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
