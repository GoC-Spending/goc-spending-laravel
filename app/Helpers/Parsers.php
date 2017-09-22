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
                $values[$labelToKey[$keyNode]] = Cleaners::cleanHtmlValue($valueNodes[$index]);
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
}
