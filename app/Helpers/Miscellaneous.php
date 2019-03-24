<?php
namespace App\Helpers;

class Miscellaneous
{

    // CSV value => scraper value
    // or, initial value => intended value for merging departments together
    public static $ownerAcronymMapping = [
        // No longer needed in the 2019-03-24 version of the CSV file
        'feddevontario' => 'feddev',
        'statcan' => 'stats',
        'pwgsc' => 'pspc',
        'jus' => 'just',
        'infc' => 'infra',
        'aandc' => 'inac',
        'aafc' => 'agr',
        // Still useful as of 2019-03-24
        'cic' => 'ircc',
        'dfatd' => 'gac',
        // Mergers across several owner departments:
        // Passport Canada to IRCC
        'irccpc' => 'ircc',
        // ServiceCanada to ESDC
        'sc' => 'esdc',
    ];

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
