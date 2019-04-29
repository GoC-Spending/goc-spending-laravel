<?php
namespace App;

use App\Helpers\Cleaners;
use App\Helpers\ContractDataProcessors;
use App\Helpers\Parsers;
use App\Helpers\Paths;
use App\Helpers\Miscellaneous;
use App\VendorData;
use App\ChartOps;
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



    public static function run($filename, $dataMethod, $dataMethodParams = [], $chartMethod = '', $chartMethodParams = [])
    {

        if ($dataMethodParams) {
          // So far, all the analysis functions take one parameter at most (e.g. department or vendor)
          // May need to revisit this for multiple parameters (as is the case for chart functions)
            $data = self::$dataMethod($dataMethodParams);
        } else {
            $data = self::$dataMethod();
        }
      
        self::saveAnalysisCsvFile($filename, $data);

        if ($chartMethod) {
            $html = ChartOps::$chartMethod($filename, $data, $chartMethodParams);
            ChartOps::saveChartHtml($filename, $html);
        }
    }

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
        if (is_array($input) && count($input) >= 1) {
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

        foreach ($input as $object) {
            $outputString .= implode(',', array_values(get_object_vars($object))) . "\n";
        }

        return $outputString;
    }

    public static function generateAnalysisCsvFiles()
    {

        // Entries by department, by year
        self::run('general/entries-by-department-by-year', 'entriesByYear', [], 'arrayToChartJsStacked', [
          'useConfigYears' => 1,
          'valueColumn' => 'total_entries',
          'labelColumn' => 'owner_acronym',
          'timeColumn' => 'source_year',
          ]);
        
        // Entries by year, overall (government-wide)
        self::run('general/entries-by-year', 'entriesByYearOverall', [], 'arrayToChartJsStackedTranspose', [
          'useConfigYears' => 1,
          'valueColumns' => ['total_contracts', 'total_amendments'],
          'timeColumn' => 'source_year',
          'colorMapping' => 'keyword',
          ]);

        // Entries by fiscal quarter, overall
        self::run('general/entries-by-fiscal', 'entriesByFiscalOverall', [], 'arrayToChartJsStackedTranspose', [
          'useConfigFiscal' => 1,
          'valueColumns' => ['total_contracts', 'total_amendments'],
          'timeColumn' => 'source_fiscal',
          'colorMapping' => 'keyword',
          ]);

        // Total spending by year, overall
        self::run('general/effective-overall-total-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'effectiveOverallTotalByYear', [], 'arrayToChartJsSingle', ['timeColumn' => 'effective_year']);

        // (Deferred for now - entries by fiscal quarter, by department)
        // self::run('general/entries-by-fiscal', 'entriesByFiscal');
    
        self::run('general/effective-total-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'effectiveTotalByYear', [], 'arrayToChartJsStacked', [
          'useConfigYears' => 1,
          'valueColumn' => 'sum_yearly_value',
          'labelColumn' => 'owner_acronym',
          'timeColumn' => 'effective_year',
          'chartOptions' => 'timeStackedCurrency',
          ]);
        
          dd('y');
        
        self::run('general/effective-overall-total-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'effectiveOverallTotalByYear');

        self::run('general/largest-companies-by-effective-value-total-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValue');
    
        self::run('general/largest-companies-by-effective-value-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValueByYear');
    
        self::run('general/largest-companies-by-entries-total-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntries');

        self::run('general/largest-companies-by-entries-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntriesByYear');

        dd('here');

        $ownerAcronyms = self::allOwnerAcronyms();

        foreach ($ownerAcronyms as $ownerAcronym) {
            self::run("departments/$ownerAcronym/largest-companies-by-effective-value-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValue', $ownerAcronym);
    
            self::run("departments/$ownerAcronym/largest-companies-by-effective-value-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValue', $ownerAcronym);
      
            self::run("departments/$ownerAcronym/largest-companies-by-entries-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntries', $ownerAcronym);

            self::run("departments/$ownerAcronym/largest-companies-by-entries-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntriesByYear', $ownerAcronym);
        }

        $vendors = self::largestVendorNamesByEffectiveValue();

        foreach ($vendors as $vendor) {
            $vendorSlug = Str::slug($vendor);

            self::run("vendors/$vendorSlug/largest-departments-by-effective-value-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestDepartmentsByEffectiveValue', $vendor);
    
            self::run("vendors/$vendorSlug/largest-departments-by-effective-value-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestDepartmentsByEffectiveValueByYear', $vendor);
      
            self::run("vendors/$vendorSlug/largest-departments-by-entries-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestDepartmentsByEntries', $vendor);

            self::run("vendors/$vendorSlug/largest-departments-by-entries-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestDepartmentsByEntriesByYear', $vendor);
        }
    }

    // Gets the total number of contract or amendment entries by year, government-wide
    public static function entriesByYearOverall()
    {

        $results = DB::select(DB::raw('
    SELECT source_year, COUNT("id") as total_entries,
COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
    FROM "l_contracts"
    WHERE source_year IS NOT NULL
    AND gen_is_duplicate::integer = 0
    AND gen_is_error::integer = 0
    GROUP BY source_year
    ORDER BY source_year
    LIMIT 50000
    '));

        return $results;
    }

    // Gets the total number of contracts or amendment entries by fiscal quarter, by department
    public static function entriesByFiscalOverall()
    {

        $results = DB::select(DB::raw('
    SELECT source_fiscal, COUNT("id") as total_entries,
COUNT("id") filter (where gen_is_amendment::integer = 0) as total_contracts,
COUNT("id") filter (where gen_is_amendment::integer = 1) as total_amendments
    FROM "l_contracts"
    WHERE source_fiscal IS NOT NULL
    AND gen_is_duplicate::integer = 0
    AND gen_is_error::integer = 0
    GROUP BY source_fiscal
    ORDER BY source_fiscal
    LIMIT 50000
    '));

        return $results;
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
    AND gen_is_error::integer = 0
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
    AND gen_is_error::integer = 0
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

    public static function effectiveOverallTotalByYear()
    {

        $results = DB::select(
            DB::raw('
    SELECT effective_year, SUM("yearly_value") as sum_yearly_value
    FROM "exports_v2"
    WHERE effective_year <= :endYear
    AND effective_year >= :startYear
    GROUP BY effective_year
    ORDER BY effective_year ASC
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
        AND gen_is_error::integer = 0
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
        AND gen_is_error::integer = 0
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
          AND gen_is_error::integer = 0
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
        AND gen_is_error::integer = 0
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
        AND gen_is_error::integer = 0
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
          AND gen_is_error::integer = 0
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
