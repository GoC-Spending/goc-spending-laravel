<?php

namespace App\Helpers;

class ContractDataProcessor
{

    public static function cleanParsedArray(&$values)
    {

        $values['startYear'] = \App\Helpers\Helpers::extractYearFromDate($values['contractPeriodStart']);
        $values['endYear'] = \App\Helpers\Helpers::extractYearFromDate($values['contractPeriodEnd']);

        $values['originalValue'] = \App\Helpers\Helpers::cleanContractValue($values['originalValue']);
        $values['contractValue'] = \App\Helpers\Helpers::cleanContractValue($values['contractValue']);

        if (!$values['contractValue']) {
            $values['contractValue'] = $values['originalValue'];
        }

        if ($values['originalValue'] == 0) {
            $values['originalValue'] = $values['contractValue'];
        }

        // Check for error-y non-unicode characters
        $values['referenceNumber'] = \App\Helpers\Helpers::cleanText($values['referenceNumber']);
        $values['vendorName'] = \App\Helpers\Helpers::cleanText($values['vendorName']);
        $values['comments'] = \App\Helpers\Helpers::cleanText($values['comments']);
        $values['description'] = \App\Helpers\Helpers::cleanText($values['description']);
        $values['extraDescription'] = \App\Helpers\Helpers::cleanText($values['extraDescription']);
    }

    public static function generateAdditionalMetadata(&$values)
    {

        if ($values['sourceYear'] && $values['sourceQuarter']) {
            // Generate a more traditional "20162017-Q3"
            $values['sourceFiscal'] = $values['sourceYear'] . (substr($values['sourceYear'], 2, 2) + 1) . '-Q' . $values['sourceQuarter'];
        }
    }
}
