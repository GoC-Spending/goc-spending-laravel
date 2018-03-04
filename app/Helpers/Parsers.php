<?php


namespace App\Helpers;

use XPathSelector\Selector;

class Parsers
{

    /**
     * Extract contract data sorted in standard key/value pairs in an HTML <table>, using XPath.
     *
     * @param string $html               The contract HTML to search through.
     * @param string $keyXpath           The XPath selector for keys from contract key/value pairs.
     * @param string $valueXpath         The XPath selector for values from contract key/value pairs.
     * @param string $periodSplitString  The string that marks a contract date range. (Often has "to" in it.)
     * @param array  $keyArray           The list of keys (from contract key/value pairs) to collect values for.
     *
     * @return array  The contract data, sorted into key/value pairs.
     */
    public static function extractContractDataViaGenericXpathParser($html, $keyXpath, $valueXpath, $periodSplitString, $keyArray = [])
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
            $cleanKeys[$key] = Cleaners::cleanLabelText($label);
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

        foreach ($keyNodes as $index => $keyNode) {
            $keyNode = Cleaners::cleanLabelText($keyNode);

            if (isset($labelToKey[$keyNode]) && $labelToKey[$keyNode] && isset($valueNodes[$index])) {
                $values[$labelToKey[$keyNode]] = Cleaners::removeLinebreaks(Cleaners::cleanHtmlValue($valueNodes[$index]));
            }
        }

        // Change the "to" range into start and end values:
        if (isset($values['contractPeriodRange']) && $values['contractPeriodRange']) {
            $split = explode($periodSplitString, $values['contractPeriodRange']);

            if (isset($split[0]) && isset($split[1])) {
                $values['contractPeriodStart'] = trim($split[0]);
                $values['contractPeriodEnd'] = trim($split[1]);
            }
        }

        return $values;
    }

    /**
     * Run an XPath query on a chunk of HTML, optionally filtering the result
     * via a RegEx pattern.
     *
     * @param string $html          The HTML to search.
     * @param string $xpathQuery    The XPath query to search with.
     * @param string $regexPattern  The (optional) RegEx pattern to filter the result with.
     *
     * @return string
     */
    public static function xpathRegexComboSearch($html, $xpathQuery, $regexPattern = null)
    {
        $output = '';

        $xs = Selector::loadHTML($html);
        $text = $xs->find($xpathQuery)->innerHTML();

        if (null === $regexPattern) {
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

    /**
     * Pull an array of items, selected via an XPath selector, from an
     * HTML page.
     *
     * @param $htmlSource string  The HTML to run the XPath selector on.
     * @param $xpath      string  The XPath selector to extract the items.
     *
     * @return string[]  The items converted to strings, stored in an array, and deduped.
     */
    public static function getArrayFromHtmlViaXpath($htmlSource, $xpath)
    {
        $xs = Selector::loadHTML($htmlSource);

        $items = $xs->findAll($xpath)->map(function ($node, $index) {
            return (string)$node;
        });

        return array_unique($items);
    }

    /**
     * Extract a year from a date string.
     *
     * @param string $dateInput  The date.
     *
     * @return bool|integer
     */
    public static function extractYearFromDate($dateInput)
    {
        $matches = [];
        $pattern = '/([1-2][0-9][0-9][0-9])/';

        preg_match($pattern, $dateInput, $matches);

        if (!empty($matches)) {
            return intval($matches[1]);
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
}
