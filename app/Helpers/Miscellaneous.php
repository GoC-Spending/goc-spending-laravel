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

    public static function getColorByKeyword($keyword, $border = 0)
    {
        // Chart.helpers.color("#11A579").alpha(0.7).rgbString()
        $colorMap = [
            'total_contracts' => [
                // #B1FA83
                'color' => 'rgba(124, 245, 90, 0.7)',
                'borderColor' => 'rgb(75, 242, 28)',
            ],
            'total_amendments' => [
                'color' => 'rgba(0, 176, 199, 0.7)',
                'borderColor' => 'rgb(0, 140, 158)',
            ],
        ];

        if ($border) {
            return data_get($colorMap, "$keyword.borderColor");
        } else {
            return data_get($colorMap, "$keyword.color");
        }
    }

    public static function getColorByIndex($index, $border = 0)
    {
        // Generated via
        // https://www.colorbox.io/
        // with help from Chart.js's color functions
        $colorMap = array (
            0 =>
            array (
              'color' => 'rgb(51, 0, 255)',
              'borderColor' => 'rgb(41, 0, 204)',
            ),
            1 =>
            array (
              'color' => 'rgb(255, 0, 229)',
              'borderColor' => 'rgb(204, 0, 184)',
            ),
            2 =>
            array (
              'color' => 'rgb(0, 255, 102)',
              'borderColor' => 'rgb(0, 204, 82)',
            ),
            3 =>
            array (
              'color' => 'rgb(0, 255, 255)',
              'borderColor' => 'rgb(0, 204, 204)',
            ),
            4 =>
            array (
              'color' => 'rgb(204, 255, 0)',
              'borderColor' => 'rgb(163, 204, 0)',
            ),
            5 =>
            array (
              'color' => 'rgb(128, 255, 0)',
              'borderColor' => 'rgb(102, 204, 0)',
            ),
            6 =>
            array (
              'color' => 'rgb(255, 0, 76)',
              'borderColor' => 'rgb(204, 0, 61)',
            ),
            7 =>
            array (
              'color' => 'rgb(51, 255, 0)',
              'borderColor' => 'rgb(41, 204, 0)',
            ),
            8 =>
            array (
              'color' => 'rgb(0, 25, 255)',
              'borderColor' => 'rgb(0, 20, 204)',
            ),
            9 =>
            array (
              'color' => 'rgb(0, 179, 255)',
              'borderColor' => 'rgb(0, 143, 204)',
            ),
            10 =>
            array (
              'color' => 'rgb(255, 230, 0)',
              'borderColor' => 'rgb(204, 184, 0)',
            ),
            11 =>
            array (
              'color' => 'rgb(255, 77, 0)',
              'borderColor' => 'rgb(204, 61, 0)',
            ),
            12 =>
            array (
              'color' => 'rgb(255, 153, 0)',
              'borderColor' => 'rgb(204, 122, 0)',
            ),
            13 =>
            array (
              'color' => 'rgb(204, 0, 255)',
              'borderColor' => 'rgb(163, 0, 204)',
            ),
            14 =>
            array (
              'color' => 'rgb(255, 0, 153)',
              'borderColor' => 'rgb(204, 0, 122)',
            ),
            15 =>
            array (
              'color' => 'rgb(0, 255, 25)',
              'borderColor' => 'rgb(0, 204, 20)',
            ),
            16 =>
            array (
              'color' => 'rgb(0, 102, 255)',
              'borderColor' => 'rgb(0, 82, 204)',
            ),
            17 =>
            array (
              'color' => 'rgb(255, 0, 0)',
              'borderColor' => 'rgb(204, 0, 0)',
            ),
            18 =>
            array (
              'color' => 'rgb(0, 255, 178)',
              'borderColor' => 'rgb(0, 204, 143)',
            ),
            19 =>
            array (
              'color' => 'rgb(128, 0, 255)',
              'borderColor' => 'rgb(102, 0, 204)',
            ),
          );

        // If the index is higher than the number of entries, then loop back around using modulo:
        $effectiveIndex = $index % count($colorMap);

        if ($border) {
            return data_get($colorMap, "$effectiveIndex.borderColor");
        } else {
            return data_get($colorMap, "$effectiveIndex.color");
        }
    }
}
