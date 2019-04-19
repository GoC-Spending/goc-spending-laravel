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
use Illuminate\Support\Str;

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

        if (! $csv) {
            echo "No CSV data provided for $filename\n";
            return false;
        }

        $filepath = storage_path() . '/' . env('STORAGE_RELATIVE_ANALYSIS_FOLDER');

      // Optionally include a directory along with the filename (e.g. 'general/entriesByYear')
        if (pathinfo($filename, PATHINFO_DIRNAME) != '.') {
            $filepath .= pathinfo($filename, PATHINFO_DIRNAME) . '/';
        }

      
      // If the CSV folder doesn't exist yet, create it:
        if (! is_dir($filepath)) {
            mkdir($filepath, 0755, true);
        }

        if (! is_string($csv)) {
            $csv = self::arrayToCsv($csv);
        }

        file_put_contents($filepath . pathinfo($filename, PATHINFO_FILENAME) . '.csv', $csv);

        echo "Exported $filename at " . date('Y-m-d H:i:s') . "\n";
        return true;
    }

    public static function createCsvHeaderKeys($input)
    {
        if (is_array($input) && count($input) > 1) {
            $keys = array_keys(get_object_vars($input[0]));
            return $keys;
        } else {
            return [];
        }
    }

    public static function arrayToCsv($input)
    {

        if (! $input) {
            return null;
        }

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

        $ownerAcronyms = self::allOwnerAcronyms();

        foreach ($ownerAcronyms as $ownerAcronym) {
            self::saveAnalysisCsvFile("departments/$ownerAcronym/largest-companies-by-effective-value-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEffectiveValue($ownerAcronym));
    
            self::saveAnalysisCsvFile("departments/$ownerAcronym/largest-companies-by-effective-value-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEffectiveValueByYear($ownerAcronym));
      
            self::saveAnalysisCsvFile("departments/$ownerAcronym/largest-companies-by-entries-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEntries($ownerAcronym));

            self::saveAnalysisCsvFile("departments/$ownerAcronym/largest-companies-by-entries-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestCompaniesByEntriesByYear($ownerAcronym));
        }

        $vendors = self::largestVendorNamesByEffectiveValue();

        foreach ($vendors as $vendor) {
            $vendorSlug = Str::slug($vendor);

            self::saveAnalysisCsvFile("vendors/$vendorSlug/largest-departments-by-effective-value-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestDepartmentsByEffectiveValue($vendor));
    
            self::saveAnalysisCsvFile("vendors/$vendorSlug/largest-departments-by-effective-value-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestDepartmentsByEffectiveValueByYear($vendor));
      
            self::saveAnalysisCsvFile("vendors/$vendorSlug/largest-departments-by-entries-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestDepartmentsByEntries($vendor));

            self::saveAnalysisCsvFile("vendors/$vendorSlug/largest-departments-by-entries-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], self::largestDepartmentsByEntriesByYear($vendor));
        }
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
    public static function largestCompaniesByEffectiveValue($ownerAcronym = '')
    {

        $query = '
        SELECT vendor_normalized, SUM("yearly_value") as sum_effective_value
        FROM "exports_v2"
        WHERE effective_year <= :endYear
        AND effective_year >= :startYear
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorLimit' => self::$config['vendorLimit'],
        ];

        if ($ownerAcronym) {
            $query .= 'AND owner_acronym = :ownerAcronym';
            $params['ownerAcronym'] = $ownerAcronym;
        }

        $query .= '
        GROUP BY vendor_normalized
        ORDER BY sum_effective_value DESC
        LIMIT :vendorLimit
      ';


        $results = DB::select(DB::raw($query), $params);

        return $results;
    }

  // Gets the largest companies ordered by effective value (over the total time range specified, e.g. multiple years.)
    public static function largestCompaniesByEffectiveValueByYear($ownerAcronym = '')
    {
        $query = '
        SELECT vendor_normalized, SUM("yearly_value") as sum_effective_value
        FROM "exports_v2"
        WHERE effective_year <= :endYear
        AND effective_year >= :startYear
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorLimitTimebound' => self::$config['vendorLimitTimebound'],
        ];

        if ($ownerAcronym) {
            $query .= 'AND owner_acronym = :ownerAcronym';
            $params['ownerAcronym'] = $ownerAcronym;
        }

        $query .= '
        GROUP BY vendor_normalized
        ORDER BY sum_effective_value DESC
        LIMIT :vendorLimitTimebound
      ';

        $results = DB::select(DB::raw($query), $params);

        $vendors = Arr::pluck($results, 'vendor_normalized');

      // Part 2:
      // Use the vendor list as input for a time-sorted select

        $query = '
        SELECT vendor_normalized, effective_year, SUM("yearly_value") as sum_yearly_value
        FROM "exports_v2"
        WHERE effective_year <= :endYear
        AND effective_year >= :startYear
        AND vendor_normalized = ' . self::arrayToArrayString($vendors)
        ;

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        ];

        if ($ownerAcronym) {
            $query .= 'AND owner_acronym = :ownerAcronym';
            $params['ownerAcronym'] = $ownerAcronym;
        }

        $query .= '
        GROUP BY vendor_normalized, effective_year
        ORDER BY vendor_normalized, effective_year ASC
        LIMIT 10000;
      ';

        $results = DB::select(DB::raw($query), $params);

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
        $ownerAcronyms = DB::table('exports_v2')
        ->distinct()
        ->pluck('owner_acronym');
        return $ownerAcronyms->toArray();
    }

    public static function largestVendorNamesByEffectiveValue($length = 10)
    {
        $results = self::largestCompaniesByEffectiveValue();

        $vendors = array_slice(Arr::pluck($results, 'vendor_normalized'), 0, $length);
      
        return $vendors;
    }


    // Gets the largest companies ordered by total number of entries (over the total time range specified, e.g. multiple years.)
    public static function largestCompaniesByEntries($ownerAcronym = '')
    {
        $query = '
        SELECT gen_vendor_normalized, COUNT("id") as total_entries,
        COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
        COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
        FROM "l_contracts"
        WHERE source_year IS NOT NULL
        AND source_year <= :endYear
        AND source_year >= :startYear
        AND gen_is_duplicate::integer = 0
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorLimit' => self::$config['vendorLimit'],
        ];

        if ($ownerAcronym) {
            $query .= 'AND owner_acronym = :ownerAcronym';
            $params['ownerAcronym'] = $ownerAcronym;
        }

        $query .= '
        GROUP BY gen_vendor_normalized
        ORDER BY total_entries DESC
        LIMIT :vendorLimit
      ';

        $results = DB::select(DB::raw($query), $params);

        return $results;
    }

    // Gets the largest companies ordered by total number of entries, by year.
    public static function largestCompaniesByEntriesByYear($ownerAcronym = '')
    {
        $query = '
        SELECT gen_vendor_normalized, COUNT("id") as total_entries,
        COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
        COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
        FROM "l_contracts"
        WHERE source_year IS NOT NULL
        AND source_year <= :endYear
        AND source_year >= :startYear
        AND gen_is_duplicate::integer = 0
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorLimitTimebound' => self::$config['vendorLimitTimebound'],
        ];

        if ($ownerAcronym) {
            $query .= 'AND owner_acronym = :ownerAcronym';
            $params['ownerAcronym'] = $ownerAcronym;
        }

        $query .= '
        GROUP BY gen_vendor_normalized
        ORDER BY total_entries DESC
        LIMIT :vendorLimitTimebound
      ';

        $results = DB::select(DB::raw($query), $params);

        $vendors = Arr::pluck($results, 'gen_vendor_normalized');

      // Part 2:
      // Use the vendor list as input for a time-sorted select

        $query = '
          SELECT gen_vendor_normalized, source_year, COUNT("id") as total_entries,
          COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
          COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
          FROM "l_contracts"
          WHERE source_year IS NOT NULL
          AND source_year <= :endYear
          AND source_year >= :startYear
          AND gen_vendor_normalized = ' . self::arrayToArrayString($vendors) . '
          AND gen_is_duplicate::integer = 0
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        ];

        if ($ownerAcronym) {
            $query .= 'AND owner_acronym = :ownerAcronym';
            $params['ownerAcronym'] = $ownerAcronym;
        }

        $query .= '
        GROUP BY gen_vendor_normalized, source_year
        ORDER BY gen_vendor_normalized, source_year ASC
        LIMIT 1000
      ';

        $results = DB::select(DB::raw($query), $params);

        return $results;
    }
  
    // For a specified vendor, gives the breakdown by department
    public static function largestDepartmentsByEffectiveValue($vendorName)
    {

        $query = '
        SELECT owner_acronym, SUM("yearly_value") as sum_effective_value
        FROM "exports_v2"
        WHERE effective_year <= :endYear
        AND effective_year >= :startYear
        AND vendor_normalized = :vendorName
        GROUP BY owner_acronym
        ORDER BY sum_effective_value DESC
        LIMIT :limit
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
        'limit' => self::$config['vendorLimit'],
        ];

        $results = DB::select(DB::raw($query), $params);

        return $results;
    }

    public static function largestDepartmentsByEffectiveValueByYear($vendorName)
    {
        $query = '
        SELECT owner_acronym, SUM("yearly_value") as sum_effective_value
        FROM "exports_v2"
        WHERE effective_year <= :endYear
        AND effective_year >= :startYear
        AND vendor_normalized = :vendorName
        GROUP BY owner_acronym
        ORDER BY sum_effective_value DESC
        LIMIT :vendorLimitTimebound
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
        'vendorLimitTimebound' => self::$config['vendorLimitTimebound'],
        ];



        $results = DB::select(DB::raw($query), $params);

        $ownerAcronyms = Arr::pluck($results, 'owner_acronym');

      // Part 2:
      // Use the department ownerAcronyms list as input for a time-sorted select

        $query = '
        SELECT owner_acronym, effective_year, SUM("yearly_value") as sum_yearly_value
        FROM "exports_v2"
        WHERE effective_year <= :endYear
        AND effective_year >= :startYear
        AND owner_acronym = ' . self::arrayToArrayString($ownerAcronyms) . '
        AND vendor_normalized = :vendorName
        GROUP BY owner_acronym, effective_year
        ORDER BY owner_acronym, effective_year ASC
        LIMIT 10000;
        ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
        ];

        $results = DB::select(DB::raw($query), $params);

        return $results;
    }

    public static function largestDepartmentsByEntries($vendorName)
    {
        $query = '
        SELECT owner_acronym, COUNT("id") as total_entries,
        COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
        COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
        FROM "l_contracts"
        WHERE source_year IS NOT NULL
        AND source_year <= :endYear
        AND source_year >= :startYear
        AND gen_is_duplicate::integer = 0
        AND gen_vendor_normalized = :vendorName
        GROUP BY owner_acronym
        ORDER BY total_entries DESC
        LIMIT :vendorLimit
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
        'vendorLimit' => self::$config['vendorLimit'],
        ];


        $results = DB::select(DB::raw($query), $params);

        return $results;
    }

    // Gets the largest companies ordered by total number of entries, by year.
    public static function largestDepartmentsByEntriesByYear($vendorName)
    {
        $query = '
        SELECT owner_acronym, COUNT("id") as total_entries,
        COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
        COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
        FROM "l_contracts"
        WHERE source_year IS NOT NULL
        AND source_year <= :endYear
        AND source_year >= :startYear
        AND gen_is_duplicate::integer = 0
        AND gen_vendor_normalized = :vendorName
        GROUP BY owner_acronym
        ORDER BY total_entries DESC
        LIMIT :vendorLimitTimebound
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
        'vendorLimitTimebound' => self::$config['vendorLimitTimebound'],
        ];


        $results = DB::select(DB::raw($query), $params);

        $ownerAcronyms = Arr::pluck($results, 'owner_acronym');

      // Part 2:
      // Use the vendor list as input for a time-sorted select

        $query = '
          SELECT owner_acronym, source_year, COUNT("id") as total_entries,
          COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
          COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
          FROM "l_contracts"
          WHERE source_year IS NOT NULL
          AND source_year <= :endYear
          AND source_year >= :startYear
          AND gen_vendor_normalized = :vendorName
          AND owner_acronym = ' . self::arrayToArrayString($ownerAcronyms) . '
          AND gen_is_duplicate::integer = 0
          GROUP BY owner_acronym, source_year
          ORDER BY owner_acronym, source_year ASC
          LIMIT 10000
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
        ];


        $results = DB::select(DB::raw($query), $params);

        return $results;
    }
}
