<?php
namespace App;

use App\Helpers\Paths;
use Illuminate\Support\Str;

class VendorData
{

    /**
     * Unique object instance.
     *
     * @var VendorData
     */
    private static $instance;

    public static $suffixes = [
        'LIMITED',
        'LIMITEE',
        'LIMITE',
        'LIMITACE',
        'LTE',
        'LT',
        'LTEE',
        'LLP',
        'LP',
        'PLC',
        'LCC',
        'LLC',
        'INCORPORATED',
        'INC',
        'LTD',
        'LDT',
        'CO',
        'CORP',
        'CORPORATION',
        'PLC',
        'PTY',
        'ULC',
        'LP',
        'AB',
        'SENC',
        'SENCRL',
        'SENCRLSRL',
        'SRL',
        'LLPSEN',
        'LTACE',
        'GMBH',
        'SA',
        'SPZOO',
        'SP ZOO',
        'SP Z OO',
        'SP Z O O',
        'BV',
        'B V',
        'SAS',
    ];

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

    // NOTE - Now replaced with the v2 version below
    public static function cleanupVendorNameOLD($input)
    {

        $charactersToRemove = [
            ' LIMITED',
            ' LIMITE',
            // ' LIMITEE',
            ' LCC',
            ' LLC',
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
            
            
            '"',
            ')',
            '(',
            '#',
            ':',
            ';',
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

    // Version 2 as of 2019-04-22
    public static function cleanupVendorName($input)
    {
        
        $input = str_replace('/', ' / ', $input);

        $input = strtoupper(Str::slug($input, ' '));

        foreach (self::$suffixes as $suffix) {
            $input = self::removeFromEnd($input, ' ' . $suffix);

            $input = str_replace(' ' . $suffix . ' ', ' ', $input);
        }

        $input = trim($input);
        
        return $input;
    }

    // Thanks to
    // https://stackoverflow.com/a/47689812/756641
    public static function removeFromEnd($haystack, $needle)
    {
        $length = strlen($needle);
    
        if (substr($haystack, -$length) === $needle) {
            $haystack = substr($haystack, 0, -$length);
        }
        return $haystack;
    }

    // To help add new entries to the vendor data CSV file
    // the first entry needs to match the cleanupVendorName entries above, otherwise scraped or CSV entries won't match later.
    // Usage is via Tinker, e.g.
    /*
\App\VendorData::csvEntryPrep("
WARTSILA
WARTSILA CANADA
WARTSILA CANADA (QC)
WARTSILA CANDA
WARTSILA LIPS
", 'WARTSILA')
*/
    public static function csvEntryPrep($multilineVendors, $normalizedName, $echo = 1)
    {

        $output = [];
        $outputText = '';

        $normalizedName = self::cleanupVendorName($normalizedName);

        // Handle either comma separation, or linebreaks
        $multilineVendors = str_replace("\n", ',', $multilineVendors);
        $multilineVendors = str_replace(',,', ',', $multilineVendors);

        $vendors = explode(',', $multilineVendors);

        foreach ($vendors as &$vendor) {
            $vendor = self::cleanupVendorName($vendor);
        }

        foreach ($vendors as $vendor) {
            if (trim($vendor)) {
                // If it's identical, it isn't needed in the list:
                if ($vendor != $normalizedName) {
                    $output[$vendor] = $normalizedName;
                }
            }
        }

        foreach ($output as $name => $normalized) {
            $outputText .= trim($normalized) . ',' . trim($name) . "\n";
        }

        // For convenient use via Artisan Tinker
        if ($echo) {
            echo "\n\n";
            echo $outputText;
        } else {
            return $outputText;
        }
    }


    // Re-sorts the vendor CSV file (in goc-spending-vendors)
    // usage is via Tinker, e.g.
    // \App\VendorData::resortVendorDataCsv(1)
    // Using the saveChanges flag will modify the CSV file (which is git-tracked).
    public static function resortVendorDataCsv($saveChanges = 0)
    {

        $vendorData = self::getVendorCsvData();

        // dd($vendorData);

        $vendorKeys = array_keys($vendorData);
        $outputArray = [];

        sort($vendorKeys, SORT_STRING);

        foreach ($vendorKeys as $parent) {
            $parent = VendorData::cleanupVendorName($parent);
            $outputArray[$parent] = [];
        }

        foreach ($vendorData as $parent => $childArray) {
            $parent = VendorData::cleanupVendorName($parent);
            foreach ($childArray as $child) {
                $outputArray[$parent][] = VendorData::cleanupVendorName($child);
            }
        }
        foreach ($outputArray as $parent => &$childArray) {
            sort($childArray, SORT_STRING);
            $childArray = array_unique($childArray, SORT_STRING);
        }
        // dd($outputArray);

        $outputCsvString = "Parent company,Company name\n";
        foreach ($outputArray as $parent => &$childArray) {
            foreach ($childArray as $child) {
                $outputCsvString .= $parent . "," . $child . "\n";
            }
        }

        if ($saveChanges) {
            $filepath = Paths::getVendorDataDirectory() . "vendor_data.csv";

            file_put_contents($filepath, $outputCsvString);
            echo "Finished exporting vendor entries. \n";
        } else {
            echo $outputCsvString;
        }
    }
}
