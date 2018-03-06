<?php

namespace App\Helpers;

class ContractDataProcessors
{

    /**
     * Clean the contract data according to its fields.
     *
     * @param array $values
     *
     * @return array
     */
    public static function cleanParsedArray($values)
    {
        $values['startYear'] = Parsers::extractYearFromDate($values['contractPeriodStart']);
        $values['endYear'] = Parsers::extractYearFromDate($values['contractPeriodEnd']);

        $values['originalValue'] = Cleaners::cleanContractValue($values['originalValue']);
        $values['contractValue'] = Cleaners::cleanContractValue($values['contractValue']);

        if (!$values['contractValue']) {
            $values['contractValue'] = $values['originalValue'];
        }

        // Commenting this out - if no originalValue exists in the HTML, don't add it here
        // if ($values['originalValue'] == 0) {
        //     $values['originalValue'] = $values['contractValue'];
        // }

        // Check for error-y non-unicode characters
        $values['referenceNumber'] = Cleaners::convertToUtf8($values['referenceNumber']);
        $values['vendorName'] = Cleaners::convertToUtf8($values['vendorName']);
        $values['comments'] = Cleaners::convertToUtf8($values['comments']);
        $values['description'] = Cleaners::convertToUtf8($values['description']);
        $values['extraDescription'] = Cleaners::convertToUtf8($values['extraDescription']);

        return $values;
    }

    /**
     * Add additional metadata to the contract based on the already-set fields.
     *
     * @param array $values
     *
     * @return array
     */
    public static function generateAdditionalMetadata($values)
    {
        if ($values['sourceYear'] && $values['sourceQuarter']) {
            // Generate a more traditional "20162017-Q3"
            $values['sourceFiscal'] = $values['sourceYear'] . str_pad((substr($values['sourceYear'], 2, 2) + 1), 2, '0', STR_PAD_LEFT) . '-Q' . $values['sourceQuarter'];
        }

        return $values;
    }

    /**
     * Check that required contract values are present, adding them if not.
     *
     * @param array $contract    The contract to clean.
     * @param array $vendorData  The data for the contract vendor, if applicable.
     *
     * @return array
     */
    public static function assureRequiredContractValues($contract, $vendorData = [])
    {
        // In some cases, entries are missing a contract period start, but do have a contract date. If so, use that instead:
        if (!$contract['startYear']) {
            $contract['startYear'] = Parsers::extractYearFromDate($contract['contractDate']);
        }

        // If there's no end year, assume that it's the same as the start year:
        if (!$contract['endYear']) {
            if ($contract['deliveryDate']) {
                $contract['endYear'] = Parsers::extractYearFromDate($contract['deliveryDate']);
            } else {
                $contract['endYear'] = $contract['startYear'];
            }
        }

        // If there's no original contract value, use the current value:
        // (Update: don't do that.)
        // if (!$contract['originalValue']) {
        //     $contract['originalValue'] = $contract['contractValue'];
        // }

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
                $contract[$textField] = Cleaners::removeLinebreaks($contract[$textField]);
            }
        }

        return $contract;
    }

    /**
     * Cleanup contract values prior to exporting to JSON, removing un-used fields.
     *
     * @param array $contract    The contract to clean.
     *
     * @return array
     */
    public static function cleanupExportedContractValues($contract)
    {

        $keysToRemove = [
            'contractPeriodRange',
            'amendedValues',
            'yearsDuration',
            'valuePerYear',
        ];

        foreach ($keysToRemove as $key) {
            unset($contract[$key]);
        }

        return $contract;
    }
}
