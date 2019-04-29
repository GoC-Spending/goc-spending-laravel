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
              'color' => 'rgba(234, 255, 212, 0.7)',
              'borderColor' => 'rgb(191, 255, 122)',
            ),
            1 =>
            array (
              'color' => 'rgba(232, 254, 209, 0.7)',
              'borderColor' => 'rgb(188, 252, 120)',
            ),
            2 =>
            array (
              'color' => 'rgba(227, 252, 203, 0.7)',
              'borderColor' => 'rgb(179, 247, 115)',
            ),
            3 =>
            array (
              'color' => 'rgba(219, 249, 194, 0.7)',
              'borderColor' => 'rgb(172, 241, 116)',
            ),
            4 =>
            array (
              'color' => 'rgba(207, 246, 182, 0.7)',
              'borderColor' => 'rgb(156, 236, 105)',
            ),
            5 =>
            array (
              'color' => 'rgba(190, 240, 167, 0.7)',
              'borderColor' => 'rgb(139, 228, 98)',
            ),
            6 =>
            array (
              'color' => 'rgba(168, 234, 149, 0.7)',
              'borderColor' => 'rgb(114, 221, 85)',
            ),
            7 =>
            array (
              'color' => 'rgba(140, 226, 129, 0.7)',
              'borderColor' => 'rgb(89, 213, 72)',
            ),
            8 =>
            array (
              'color' => 'rgba(108, 217, 109, 0.7)',
              'borderColor' => 'rgb(56, 204, 59)',
            ),
            9 =>
            array (
              'color' => 'rgba(86, 208, 105, 0.7)',
              'borderColor' => 'rgb(52, 183, 71)',
            ),
            10 =>
            array (
              'color' => 'rgba(64, 197, 107, 0.7)',
              'borderColor' => 'rgb(49, 160, 84)',
            ),
            11 =>
            array (
              'color' => 'rgba(45, 186, 116, 0.7)',
              'borderColor' => 'rgb(36, 148, 92)',
            ),
            12 =>
            array (
              'color' => 'rgba(28, 176, 130, 0.7)',
              'borderColor' => 'rgb(22, 141, 103)',
            ),
            13 =>
            array (
              'color' => 'rgba(14, 166, 150, 0.7)',
              'borderColor' => 'rgb(11, 131, 119)',
            ),
            14 =>
            array (
              'color' => 'rgba(3, 140, 158, 0.7)',
              'borderColor' => 'rgb(3, 115, 130)',
            ),
            15 =>
            array (
              'color' => 'rgba(0, 100, 151, 0.7)',
              'borderColor' => 'rgb(0, 82, 122)',
            ),
            16 =>
            array (
              'color' => 'rgba(0, 64, 145, 0.7)',
              'borderColor' => 'rgb(0, 49, 112)',
            ),
            17 =>
            array (
              'color' => 'rgba(0, 31, 141, 0.7)',
              'borderColor' => 'rgb(0, 24, 112)',
            ),
            18 =>
            array (
              'color' => 'rgba(0, 1, 138, 0.7)',
              'borderColor' => 'rgb(0, 0, 112)',
            ),
            19 =>
            array (
              'color' => 'rgba(26, 0, 136, 0.7)',
              'borderColor' => 'rgb(21, 0, 112)',
            ),
            20 =>
            array (
              'color' => 'rgba(50, 0, 135, 0.7)',
              'borderColor' => 'rgb(39, 0, 107)',
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
