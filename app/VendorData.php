<?php
namespace App;

use App\Helpers\Paths;

class VendorData
{

    /**
     * Unique object instance.
     *
     * @var VendorData
     */
    private static $instance;

    public $vendorTable;

    /**
     * Return the unique plugin instance.
     *
     * @return VendorData
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        $this->vendorTable = self::reindexVendorData(self::getVendorCsvData());
    }

    public static function getVendorCsvData()
    {
      // Retrieve the list of vendors from the goc-spending-vendors repository folder
        $filepath = Paths::getVendorDataDirectory() . "vendor_data.csv";
        $vendorData = [];

        if (file_exists($filepath)) {
           // Thanks to
           // http://php.net/manual/en/function.str-getcsv.php#117692
            $csv = array_map('str_getcsv', file($filepath));
            array_walk($csv, function (&$a) use ($csv) {
                $a = array_combine($csv[0], $a);
            });
            array_shift($csv);
        } else {
            echo "Error: could not load vendor_data.csv file. Make sure the goc-spending-vendors repository exists. \n";
        }

      // Re-index according to the usual structure (parent => childArray)
        if (is_array($csv)) {
            foreach ($csv as $row) {
              // dd($row);
                if ($row['Company name']) {
                    $vendorData[$row['Parent company']][] = $row['Company name'];
                }
            }
        } else {
            echo "Error: could not parse the vendor_data.csv file. Make sure that vendor_data.csv is a valid CSV file. \n";
        }
      
        return $vendorData;
    }

    // Re-index the vendor data so that the name variants are keys and the common name is the value for each, to speed up matching in the consolidateVendorNames function:
    public static function reindexVendorData($vendors)
    {

        $vendorTable = [];

        foreach ($vendors as $vendor => $vendorNames) {
            foreach ($vendorNames as $vendorName) {
                $vendorTable[self::cleanupVendorName($vendorName)] = self::cleanupVendorName($vendor);
            }
        }

        return $vendorTable;
    }

    public function consolidateVendorNames($vendorName)
    {

        $vendorName = self::cleanupVendorName($vendorName);

        $output = $vendorName;

        if (isset($this->vendorTable[$vendorName])) {
            $output = $this->vendorTable[$vendorName];
        }

        // if($vendorName != $output) {
        //  echo "Replacing [$vendorName] with [$output]. \n";
        // }

        return $output;
    }

    public static function cleanupVendorName($input)
    {

        $charactersToRemove = [
            ',',
            "'",
            "\t",
            '.',
            ' INCORPORATED',
            ' INC',
            ' LTD',
            ' -',
            ' /',
            '/ ',
            ' LIMITED',
            ' LIMITE',
            ' LCC',
            ' LLC',
            '"',
            ')',
            '(',
            '#',
            ':',
            ' \\',
            '\\ ',
        ];

        $output = str_replace($charactersToRemove, ' ', strtoupper($input));

         // 2nd pass
        $output = str_replace($charactersToRemove, ' ', strtoupper($input));

         // Remove extra spaces
        $output = str_replace(['    ', '   ', '  '], ' ', $output);

        $trimCharacters = [
         '-',
         '&',
         '/',
         '\\',
         '*',
         '@',
        ];

        foreach ($trimCharacters as $character) {
            $output = trim($output, $character);
        }

        return trim($output);
    }
}
