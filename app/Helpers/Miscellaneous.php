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
                'color' => 'rgb(102, 204, 0)',
                'borderColor' => 'rgb(82, 163, 0)',
            ],
            'total_amendments' => [
                'color' => 'rgb(51, 51, 204)',
                'borderColor' => 'rgb(41, 41, 163)',
            ],
            'entries_below_25k' => [
              'color' => 'rgb(204, 204, 255)',
              'borderColor' => 'rgb(112, 112, 255)',
            ],
            'entries_above_25k' => [
              'color' => 'rgb(102, 51, 204)',
              'borderColor' => 'rgb(82, 41, 163)',
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
        // https://meyerweb.com/eric/tools/color-blend/#FFCC00:660000:10:hex
        // with help from Chart.js's color functions
        $colorMap = array (
          0 =>
          array (
            'color' => 'rgb(241, 185, 0)',
            'borderColor' => 'rgb(194, 149, 0)',
          ),
          1 =>
          array (
            'color' => 'rgb(255, 204, 0)',
            'borderColor' => 'rgb(204, 163, 0)',
          ),
          2 =>
          array (
            'color' => 'rgb(185, 111, 0)',
            'borderColor' => 'rgb(148, 89, 0)',
          ),
          3 =>
          array (
            'color' => 'rgb(116, 19, 0)',
            'borderColor' => 'rgb(92, 15, 0)',
          ),
          4 =>
          array (
            'color' => 'rgb(102, 0, 0)',
            'borderColor' => 'rgb(82, 0, 0)',
          ),
          5 =>
          array (
            'color' => 'rgb(130, 37, 0)',
            'borderColor' => 'rgb(102, 29, 0)',
          ),
          6 =>
          array (
            'color' => 'rgb(199, 130, 0)',
            'borderColor' => 'rgb(158, 103, 0)',
          ),
          7 =>
          array (
            'color' => 'rgb(172, 93, 0)',
            'borderColor' => 'rgb(138, 73, 0)',
          ),
          8 =>
          array (
            'color' => 'rgb(227, 167, 0)',
            'borderColor' => 'rgb(184, 135, 0)',
          ),
          9 =>
          array (
            'color' => 'rgb(158, 74, 0)',
            'borderColor' => 'rgb(128, 60, 0)',
          ),
          10 =>
          array (
            'color' => 'rgb(213, 148, 0)',
            'borderColor' => 'rgb(173, 121, 0)',
          ),
          11 =>
          array (
            'color' => 'rgb(144, 56, 0)',
            'borderColor' => 'rgb(112, 43, 0)',
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
