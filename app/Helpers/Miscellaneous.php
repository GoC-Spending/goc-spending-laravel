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
              'color' => 'rgb(63, 76, 159)',
              'borderColor' => 'rgb(51, 61, 128)',
            ),
            1 =>
            array (
              'color' => 'rgb(120, 28, 129)',
              'borderColor' => 'rgb(98, 23, 105)',
            ),
            2 =>
            array (
              'color' => 'rgb(225, 68, 39)',
              'borderColor' => 'rgb(188, 50, 26)',
            ),
            3 =>
            array (
              'color' => 'rgb(230, 103, 45)',
              'borderColor' => 'rgb(196, 78, 23)',
            ),
            4 =>
            array (
              'color' => 'rgb(183, 189, 75)',
              'borderColor' => 'rgb(151, 156, 58)',
            ),
            5 =>
            array (
              'color' => 'rgb(226, 158, 55)',
              'borderColor' => 'rgb(196, 129, 28)',
            ),
            6 =>
            array (
              'color' => 'rgb(216, 174, 61)',
              'borderColor' => 'rgb(183, 144, 36)',
            ),
            7 =>
            array (
              'color' => 'rgb(141, 188, 100)',
              'borderColor' => 'rgb(112, 161, 69)',
            ),
            8 =>
            array (
              'color' => 'rgb(104, 176, 144)',
              'borderColor' => 'rgb(77, 147, 116)',
            ),
            9 =>
            array (
              'color' => 'rgb(217, 33, 32)',
              'borderColor' => 'rgb(173, 26, 26)',
            ),
            10 =>
            array (
              'color' => 'rgb(90, 166, 169)',
              'borderColor' => 'rgb(72, 135, 137)',
            ),
            11 =>
            array (
              'color' => 'rgb(201, 184, 67)',
              'borderColor' => 'rgb(166, 150, 48)',
            ),
            12 =>
            array (
              'color' => 'rgb(69, 130, 193)',
              'borderColor' => 'rgb(52, 105, 157)',
            ),
            13 =>
            array (
              'color' => 'rgb(82, 27, 128)',
              'borderColor' => 'rgb(65, 21, 101)',
            ),
            14 =>
            array (
              'color' => 'rgb(162, 190, 86)',
              'borderColor' => 'rgb(132, 158, 61)',
            ),
            15 =>
            array (
              'color' => 'rgb(68, 47, 139)',
              'borderColor' => 'rgb(55, 38, 110)',
            ),
            16 =>
            array (
              'color' => 'rgb(64, 105, 180)',
              'borderColor' => 'rgb(50, 83, 143)',
            ),
            17 =>
            array (
              'color' => 'rgb(78, 150, 189)',
              'borderColor' => 'rgb(58, 122, 156)',
            ),
            18 =>
            array (
              'color' => 'rgb(231, 134, 50)',
              'borderColor' => 'rgb(201, 106, 24)',
            ),
            19 =>
            array (
              'color' => 'rgb(122, 184, 120)',
              'borderColor' => 'rgb(87, 160, 84)',
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
