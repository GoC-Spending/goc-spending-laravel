<?php

namespace App\Helpers;

class ContractDataProcessor
{

    /**
     * Clean the contract data according to its fields.
     *
     * @param array $values
     */
    public static function cleanParsedArray(&$values)
    {
        $values['startYear'] = \App\Helpers\Helpers::extractYearFromDate($values['contractPeriodStart']);
        $values['endYear'] = \App\Helpers\Helpers::extractYearFromDate($values['contractPeriodEnd']);

        $values['originalValue'] = Cleaners::cleanContractValue($values['originalValue']);
        $values['contractValue'] = Cleaners::cleanContractValue($values['contractValue']);

        if (!$values['contractValue']) {
            $values['contractValue'] = $values['originalValue'];
        }

        if ($values['originalValue'] == 0) {
            $values['originalValue'] = $values['contractValue'];
        }

        // Check for error-y non-unicode characters
        $values['referenceNumber'] = Cleaners::convertToUtf8($values['referenceNumber']);
        $values['vendorName'] = Cleaners::convertToUtf8($values['vendorName']);
        $values['comments'] = Cleaners::convertToUtf8($values['comments']);
        $values['description'] = Cleaners::convertToUtf8($values['description']);
        $values['extraDescription'] = Cleaners::convertToUtf8($values['extraDescription']);
    }

    /**
     * Add additional metadata to the contract based on the already-set fields.
     *
     * @param array $values
     */
    public static function generateAdditionalMetadata(&$values)
    {
        if ($values['sourceYear'] && $values['sourceQuarter']) {
            // Generate a more traditional "20162017-Q3"
            $values['sourceFiscal'] = $values['sourceYear'] . (substr($values['sourceYear'], 2, 2) + 1) . '-Q' . $values['sourceQuarter'];
        }
    }
}
