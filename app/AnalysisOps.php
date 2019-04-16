<?php
namespace App;

use App\Helpers\Cleaners;
use App\Helpers\ContractDataProcessors;
use App\Helpers\Parsers;
use App\Helpers\Paths;
use App\Helpers\Miscellaneous;
use App\VendorData;
use GuzzleHttp\Client;
use XPathSelector\Selector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class AnalysisOps
{

    public static $config = [
    'startYear' => 2008,
    'endYear' => 2017,
    'vendorLimit' => 100,
    'vendorLimitTimebound' => 10,
    ];

    public static function saveAnalysisCsvFile($filename, $csv)
    {
        $filepath = storage_path() . '/' . env('STORAGE_RELATIVE_ANALYSIS_FOLDER');

      // Optionally include a directory along with the filename (e.g. 'general/entriesByYear')
        if (pathinfo($filename, PATHINFO_DIRNAME) != '.') {
            $filepath .= pathinfo($filename, PATHINFO_DIRNAME) . '/';
        }

      // If the CSV folder doesn't exist yet, create it:
        if (! file_exists($filepath)) {
            mkdir($filepath, 0755);
        }

        if (! is_string($csv)) {
            $csv = self::arrayToCsv($csv);
        }

        file_put_contents($filepath . pathinfo($filename, PATHINFO_FILENAME) . '.csv', $csv);

        echo "Exported $filename at " . date('Y-m-d H:i:s') . "\n";
    }

    public static function createCsvHeaderKeys($input)
    {
        if (is_array($input) && count($input) > 1) {
            $keys = array_keys(get_object_vars($input[0]));
            return $keys;
        } else {
            return false;
        }
    }

    public static function arrayToCsv($input)
    {

        $outputString = '';
        $headers = self::createCsvHeaderKeys($input);

        $outputString .= implode(',', $headers) . "\n";

      // dd($outputString);

        foreach ($input as $object) {
            $outputString .= implode(',', array_values(get_object_vars($object))) . "\n";
        }

        return $outputString;
    }

    public static function generateAnalysisCsvFiles()
    {

        self::saveAnalysisCsvFile('general/entries-by-year', self::entriesByYear());
    
        self::saveAnalysisCsvFile('general/entries-by-fiscal', self::entriesByFiscal());
    
        self::saveAnalysisCsvFile('general/effective-total-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::effectiveTotalByYear());

        self::saveAnalysisCsvFile('general/largest-companies-by-effective-value-total-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEffectiveValue());
    
        self::saveAnalysisCsvFile('general/largest-companies-by-effective-value-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEffectiveValueByYear());
    
        self::saveAnalysisCsvFile('general/largest-companies-by-entries-total-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEntries());

        self::saveAnalysisCsvFile('general/largest-companies-by-entries-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEntriesByYear());
    }

  // Gets the total number of contracts or amendment entries by year, by department
    public static function entriesByYear()
    {

        $results = DB::select(DB::raw('
    SELECT owner_acronym, source_year, COUNT("id") as total_entries,
COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
    FROM "l_contracts"
    WHERE source_year IS NOT NULL
    AND gen_is_duplicate::integer = 0
    GROUP BY owner_acronym, source_year
    ORDER BY owner_acronym, source_year
    LIMIT 50000
    '));

        return $results;
    }

  // Gets the total number of contracts or amendment entries by fiscal quarter, by department
    public static function entriesByFiscal()
    {

        $results = DB::select(DB::raw('
    SELECT owner_acronym, source_fiscal, COUNT("id") as total_entries,
COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
    FROM "l_contracts"
    WHERE source_fiscal IS NOT NULL
    AND gen_is_duplicate::integer = 0
    GROUP BY owner_acronym, source_fiscal
    ORDER BY owner_acronym, source_fiscal
    LIMIT 50000
    '));

        return $results;
    }

  // Gets the total effective contract value by year and by department.
    public static function effectiveTotalByYear()
    {

        $results = DB::select(
            DB::raw('
    SELECT owner_acronym, effective_year, SUM("yearly_value") as sum_yearly_value
    FROM "exports_v2"
    WHERE effective_year <= :endYear
    AND effective_year >= :startYear
    GROUP BY owner_acronym, effective_year
    ORDER BY owner_acronym, effective_year ASC
    LIMIT 10000
    '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            ]
        );

        return $results;
    }

  // Gets the largest companies ordered by effective value (over the total time range specified, e.g. multiple years.)
    public static function largestCompaniesByEffectiveValue()
    {

        $results = DB::select(
            DB::raw('
    SELECT vendor_normalized, SUM("yearly_value") as sum_effective_value
    FROM "exports_v2"
    WHERE effective_year <= :endYear
    AND effective_year >= :startYear
    GROUP BY vendor_normalized
    ORDER BY sum_effective_value DESC
    LIMIT :vendorLimit
    '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            'vendorLimit' => self::$config['vendorLimit'],
            ]
        );

        return $results;
    }

  // Gets the largest companies ordered by effective value (over the total time range specified, e.g. multiple years.)
    public static function largestCompaniesByEffectiveValueByYear()
    {

        $results = DB::select(
            DB::raw('
    SELECT vendor_normalized, SUM("yearly_value") as sum_effective_value
    FROM "exports_v2"
    WHERE effective_year <= :endYear
    AND effective_year >= :startYear
    GROUP BY vendor_normalized
    ORDER BY sum_effective_value DESC
    LIMIT :vendorLimitTimebound
    '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            'vendorLimitTimebound' => self::$config['vendorLimitTimebound'],
            ]
        );

        $vendors = Arr::pluck($results, 'vendor_normalized');

      // return self::arrayToArrayString($vendors);

        $results = DB::select(
            DB::raw('
    SELECT vendor_normalized, effective_year, SUM("yearly_value") as sum_yearly_value
    FROM "exports_v2"
    WHERE effective_year <= :endYear
    AND effective_year >= :startYear
    AND vendor_normalized = ' . self::arrayToArrayString($vendors) . '
    GROUP BY vendor_normalized, effective_year
    ORDER BY vendor_normalized, effective_year ASC
    LIMIT 1000;
    '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            ]
        );

        return $results;
    }

    public static function arrayToArrayString($inputArray)
    {

        $outputString = implode("','", $inputArray);
        $outputString = "ANY(array['" . $outputString . "'])";
        return $outputString;
    }

    public static function allOwnerAcronyms()
    {
        $ownerAcronyms = DB::table('l_contracts')
        ->distinct()
        ->pluck('owner_acronym');
        return $ownerAcronyms->toArray();
    }


    // Gets the largest companies ordered by total number of entries (over the total time range specified, e.g. multiple years.)
    public static function largestCompaniesByEntries()
    {

        $results = DB::select(
            DB::raw('
  SELECT gen_vendor_normalized, COUNT("id") as total_entries,
  COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
  COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
      FROM "l_contracts"
      WHERE source_year IS NOT NULL
  AND source_year <= :endYear
  AND source_year >= :startYear
      AND gen_is_duplicate::integer = 0
      GROUP BY gen_vendor_normalized
      ORDER BY total_entries DESC
      LIMIT :vendorLimit
      '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            'vendorLimit' => self::$config['vendorLimit'],
            ]
        );

        return $results;
    }

    // Gets the largest companies ordered by total number of entries, by year.
    public static function largestCompaniesByEntriesByYear()
    {

        $results = DB::select(
            DB::raw('
    SELECT gen_vendor_normalized, COUNT("id") as total_entries,
    COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
    COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
        FROM "l_contracts"
        WHERE source_year IS NOT NULL
    AND source_year <= :endYear
    AND source_year >= :startYear
        AND gen_is_duplicate::integer = 0
        GROUP BY gen_vendor_normalized
        ORDER BY total_entries DESC
        LIMIT :vendorLimitTimebound
    '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            'vendorLimitTimebound' => self::$config['vendorLimitTimebound'],
            ]
        );

        $vendors = Arr::pluck($results, 'gen_vendor_normalized');

      // return self::arrayToArrayString($vendors);

        $results = DB::select(
            DB::raw('
    
    SELECT gen_vendor_normalized, source_year, COUNT("id") as total_entries,
    COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
    COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
        FROM "l_contracts"
        WHERE source_year IS NOT NULL
    AND source_year <= :endYear
    AND source_year >= :startYear
    AND gen_vendor_normalized = ' . self::arrayToArrayString($vendors) . '
        AND gen_is_duplicate::integer = 0
        GROUP BY gen_vendor_normalized, source_year
        ORDER BY gen_vendor_normalized, source_year ASC
        LIMIT 1000
    '),
            [
            'startYear' => self::$config['startYear'],
            'endYear' => self::$config['endYear'],
            ]
        );

        return $results;
    }
}
