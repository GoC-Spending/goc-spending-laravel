<?php
namespace App\Helpers;

use XPathSelector\Selector;

class Helpers
{


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
                $contract[$textField] = Cleaners::removeLinebreaks($contract[$textField]);
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
