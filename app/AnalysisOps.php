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
    'startYear' => 2010,
    'endYear' => 2019,
    'vendorLimit' => 200,
    'vendorLimitTimebound' => 10,
    ];



    public static function run($filename, $dataMethod, $dataMethodParams = [], $postProcessingParams = [])
    {

        if ($dataMethodParams) {
          // So far, all the analysis functions take one parameter at most (e.g. department or vendor)
          // May need to revisit this for multiple parameters (as is the case for chart functions)
            $data = self::$dataMethod($dataMethodParams);
        } else {
            $data = self::$dataMethod();
        }

        if ($postProcessingParams) {
            $data = self::postProcessData($data, $postProcessingParams);
        }
        
      
        self::saveAnalysisCsvFile($filename, $data);
    }

    public static function postProcessData($data, $postProcessingParams)
    {

        if ($currencyColumns = data_get($postProcessingParams, 'currencyColumns', [])) {
            foreach ($currencyColumns as $currencyColumn) {
                foreach ($data as &$item) {
                    $item->$currencyColumn = self::formatAsCurrency($item->$currencyColumn);
                }
            }
        }

        return $data;
    }

    public static function formatAsCurrency($input)
    {
      // Two decimal places, no thousands separator.
        return number_format($input, 2, '.', '');
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
        self::run('general/entries-by-department-by-year', 'entriesByYear', []);

        // Entries by department, by fiscal
        self::run('general/entries-by-department-by-fiscal', 'entriesByFiscal', []);
        
        // Entries by year, overall (government-wide)
        self::run('general/entries-by-year', 'entriesByYearOverall', []);

        // Entries by fiscal quarter, overall
        self::run('general/entries-by-fiscal', 'entriesByFiscalOverall', []);
        
        // Entries by year, overall, noting the source (scraper or CSV), errors, and duplicates
        self::run('general/entries-errors-duplicates-by-year', 'entriesErrorsDuplicatesByYear', []);

        // Entries by year, overall, noting the source (scraper or CSV), errors, and duplicates
        self::run('general/entries-above-and-below-25k-by-year', 'entriesAboveAndBelow25kByYear', [], [
          'currencyColumns' => [
            'original_sum_below_25k',
            'original_sum_above_25k',
            ]
        ]);

        // Entries by year, overall, noting original versus amendment entries and their sum (note that this is not the effective value and ends up summing multiple amendments in the same year, etc.)
        self::run('general/entries-contracts-and-amendments-by-year', 'entriesByOriginalContractAndAmendment', [], [
          'currencyColumns' => [
            'original_entries_sum',
            'most_recent_entries_sum',
            ]
        ]);
        
        // Total spending by year, overall
        self::run('general/effective-overall-total-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'effectiveOverallTotalByYear', [], [
          'currencyColumns' => [
            'sum_yearly_value',
          ]
        ]);

        // (Deferred for now - entries by fiscal quarter, by department)
        // self::run('general/entries-by-fiscal', 'entriesByFiscal');
    
        self::run('general/effective-total-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'effectiveTotalByYear', [], [
          'currencyColumns' => [
            'sum_yearly_value',
          ]
        ]);

        // Largest companies (at a government wide-level)
        // (next four CSVs)

        self::run('general/largest-companies-by-effective-value-total-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValue', [], [
          'currencyColumns' => [
            'sum_effective_value',
          ]
        ]);
    
        self::run('general/largest-companies-by-effective-value-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValueByYear', [], [
          'currencyColumns' => [
            'sum_yearly_value',
          ]
        ]);
    
        self::run('general/largest-companies-by-entries-total-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntries');

        self::run('general/largest-companies-by-entries-by-year-' . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntriesByYear');

        $ownerAcronyms = self::allOwnerAcronyms();

        foreach ($ownerAcronyms as $ownerAcronym) {
            self::run("departments/$ownerAcronym/entries-above-and-below-25k-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'entriesAboveAndBelow25kByYearByOwner', $ownerAcronym, [
              'currencyColumns' => [
                'original_sum_below_25k',
                'original_sum_above_25k',
              ]
            ]);

            self::run("departments/$ownerAcronym/entries-in-25k-range-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'entriesIn25kRangeByYearByOwner', $ownerAcronym, [
              'currencyColumns' => [
                'original_sum_below_24k',
                'original_sum_between_24k_and_26k',
                'original_sum_above_26k',
              ]
            ]);

            self::run("departments/$ownerAcronym/largest-companies-by-effective-value-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValue', $ownerAcronym, [
              'currencyColumns' => [
                'sum_effective_value',
              ]
            ]);
    
            self::run("departments/$ownerAcronym/largest-companies-by-effective-value-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEffectiveValueByYear', $ownerAcronym, [
              'currencyColumns' => [
                'sum_yearly_value',
              ]
            ]);
      
            self::run("departments/$ownerAcronym/largest-companies-by-entries-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntries', $ownerAcronym);

            self::run("departments/$ownerAcronym/largest-companies-by-entries-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestCompaniesByEntriesByYear', $ownerAcronym);
        }

        $vendors = self::largestVendorNamesByEffectiveValue(200);

        foreach ($vendors as $vendor) {
            $vendorSlug = Str::slug($vendor);

            self::run("vendors/$vendorSlug/largest-departments-by-effective-value-total-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestDepartmentsByEffectiveValue', $vendor, [
              'currencyColumns' => [
                'sum_effective_value',
              ]
            ]);
    
            self::run("vendors/$vendorSlug/largest-departments-by-effective-value-by-year-" . self::$config['startYear'] . '-to-' . self::$config['endYear'], 'largestDepartmentsByEffectiveValueByYear', $vendor, [
              'currencyColumns' => [
                'sum_yearly_value',
              ]
            ]);
      
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
    public static function effectiveTotalByYear($ownerAcronym = '')
    {

      $query = '
      SELECT owner_acronym, effective_year, SUM("yearly_value") as sum_yearly_value
    FROM "exports_v2"
    WHERE effective_year <= :endYear
    AND effective_year >= :startYear
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
      GROUP BY owner_acronym, effective_year
      ORDER BY owner_acronym, effective_year ASC
      LIMIT 10000';

      $results = DB::select(DB::raw($query), $params);

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

    public static function entriesErrorsDuplicatesByYear()
    {
        $results = DB::select(DB::raw('
        SELECT owner_acronym, source_year, COUNT("id") as total_entries,
        COUNT("id") filter (where source_origin = 1) as total_source_scraper,
        COUNT("id") filter (where source_origin = 2) as total_source_csv,
        COUNT("id") filter (where gen_is_error::integer = 1) as error_entries,
        COUNT("id") filter (where source_origin = 1 and gen_is_error::integer = 1) as error_source_scraper,
        COUNT("id") filter (where source_origin = 2 and gen_is_error::integer = 1) as error_source_csv,
        COUNT("id") filter (where gen_is_duplicate::integer = 0) as total_non_duplicate_entries,
        COUNT("id") filter (where gen_is_duplicate::integer = 1) as total_duplicate_entries
            FROM "l_contracts"
            GROUP BY owner_acronym, source_year
            ORDER BY owner_acronym, source_year
            LIMIT 50000
    '));

        return $results;
    }

    // Note that this only looks at initial contract entries, and excludes amendments:
    public static function entriesAboveAndBelow25kByYear()
    {
        $results = DB::select(DB::raw('
      SELECT owner_acronym, source_year, COUNT("id") as total_original_entries,
      COUNT("id") filter (where contract_value < 25000) as entries_below_25k,
      COUNT("id") filter (where contract_value >= 25000) as entries_above_25k,
      SUM("contract_value") filter (where contract_value < 25000) as original_sum_below_25k,
      SUM("contract_value") filter (where contract_value >= 25000) as original_sum_above_25k
      
          FROM "l_contracts"
          WHERE source_fiscal IS NOT NULL
          AND gen_is_duplicate::integer = 0
          AND gen_is_error::integer = 0
          AND gen_is_amendment::integer = 0
          GROUP BY owner_acronym, source_year
          ORDER BY owner_acronym, source_year
          LIMIT 50000
  '));

        return $results;
    }

    // Note that this doesn't factor in effective values, and multiple amendments will artificially inflate the sum values here:
    public static function entriesByOriginalContractAndAmendment()
    {
        $results = DB::select(DB::raw('
      SELECT owner_acronym, source_year, COUNT("id") as total_entries,
COUNT("id") filter (where gen_is_amendment::integer = 0) as original_contract_entries,
COUNT("id") filter (where gen_is_amendment::integer = 1) as amendment_entries,
COUNT("id") filter (where gen_is_most_recent_value::integer = 1) as most_recent_entries,

SUM("contract_value") filter (where gen_is_amendment::integer = 0) as original_entries_sum,
SUM("contract_value") filter (where gen_is_most_recent_value::integer = 1) as most_recent_entries_sum

    FROM "l_contracts"
    WHERE source_fiscal IS NOT NULL
    AND gen_is_duplicate::integer = 0
    AND gen_is_error::integer = 0
    GROUP BY owner_acronym, source_year
    ORDER BY owner_acronym, source_year
    LIMIT 50000
  '));

        return $results;
    }

    public static function entriesAboveAndBelow25kByYearByOwner($ownerAcronym)
    {
        $query = '
        SELECT source_year, COUNT("id") as total_original_entries,
        COUNT("id") filter (where contract_value < 25000) as entries_below_25k,
        COUNT("id") filter (where contract_value >= 25000) as entries_above_25k,
        SUM("contract_value") filter (where contract_value < 25000) as original_sum_below_25k,
        SUM("contract_value") filter (where contract_value >= 25000) as original_sum_above_25k
        
            FROM "l_contracts"
            WHERE source_year IS NOT NULL
        AND source_year <= :endYear
        AND source_year >= :startYear
            AND gen_is_duplicate::integer = 0
            AND gen_is_error::integer = 0
            AND gen_is_amendment::integer = 0
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
        GROUP BY source_year
        ORDER BY source_year
        LIMIT 50000
      ';

        $results = DB::select(DB::raw($query), $params);

        return $results;
    }

    public static function entriesIn25kRangeByYearByOwner($ownerAcronym)
    {
        $query = '
        SELECT source_year, COUNT("id") as total_original_entries,
        COUNT("id") filter (where contract_value < 24000) as entries_below_24k,
        COUNT("id") filter (where contract_value >= 24000 AND contract_value < 26000) as entries_between_24k_and_26k,
        COUNT("id") filter (where contract_value >= 26000) as entries_above_26k,

        SUM("contract_value") filter (where contract_value < 24000) as original_sum_below_24k,
        SUM("contract_value") filter (where contract_value >= 24000 AND contract_value < 26000) as original_sum_between_24k_and_26k,
        SUM("contract_value") filter (where contract_value >= 26000) as original_sum_above_26k
        
            FROM "l_contracts"
            WHERE source_year IS NOT NULL
        AND source_year <= :endYear
        AND source_year >= :startYear
            AND gen_is_duplicate::integer = 0
            AND gen_is_error::integer = 0
            AND gen_is_amendment::integer = 0
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
        GROUP BY source_year
        ORDER BY source_year
        LIMIT 50000
      ';

        $results = DB::select(DB::raw($query), $params);

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
        ->orderBy('owner_acronym', 'asc')
        ->pluck('owner_acronym');
        return $ownerAcronyms->toArray();
    }

    public static function largestVendorNamesByEffectiveValue($length = 10, $sortByName = 0)
    {
        $results = self::largestCompaniesByEffectiveValue();

        $vendors = array_slice(Arr::pluck($results, 'vendor_normalized'), 0, $length);

        if ($sortByName) {
            sort($vendors, SORT_STRING);
        }
      
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
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
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
        LIMIT 20000;
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
      ';

        $params = [
        'startYear' => self::$config['startYear'],
        'endYear' => self::$config['endYear'],
        'vendorName' => $vendorName,
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
          LIMIT 20000
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
