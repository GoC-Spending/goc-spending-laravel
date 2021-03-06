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

class DbOps
{

    public static function importJsonFileToDatabase($jsonPath, $contractId)
    {

        $json = json_decode(file_get_contents($jsonPath), 1);

        return self::importJsonDataToDatabase($json, $contractId, $jsonPath);
    }

    public static function importJsonDataToDatabase($json, $contractId, $jsonPath)
    {

        $vendorData = VendorData::getInstance();

        // Clean up empty string entries
        $json = array_map(function ($entry) {
            if ($entry === '') {
                $entry = null;
            }
            return $entry;
        }, $json);

        if (! isset($json['sourceOrigin'])) {
            $json['sourceOrigin'] = 1;
        }
        if (! isset($json['sourceCsvFilename'])) {
            $json['sourceCsvFilename'] = null;
        }

        $output = [
            'json_id' => $json['uuid'],
            'vendor_name' => strtoupper($json['vendorName']),
            'owner_acronym' => $json['ownerAcronym'],
            'contract_value' => $json['contractValue'],
            'original_value' => $json['originalValue'],
            'reference_number' => $json['referenceNumber'],
            'raw_contract_date' => $json['contractDate'],
            'raw_delivery_date' => $json['deliveryDate'],
            'raw_contract_period_start' => $json['contractPeriodStart'],
            'raw_contract_period_end' => $json['contractPeriodEnd'],
            'object_code' => $json['objectCode'],
            'description' => $json['description'],
            'extra_description' => $json['extraDescription'],
            'comments' => $json['comments'],
            'source_year' => $json['sourceYear'],
            'source_quarter' => $json['sourceQuarter'],
            'source_fiscal' => $json['sourceFiscal'],
            'source_origin' => $json['sourceOrigin'],
            'source_csv_filename' => $json['sourceCsvFilename'],
            'gen_start_year' => $json['startYear'],
            'gen_end_year' => $json['endYear'],
            // Re-run the cleanup function:
            'gen_vendor_clean' => VendorData::cleanupVendorName($json['vendorName']),
            'gen_vendor_normalized' => $vendorData->consolidateVendorNames($json['vendorName']),
            'gen_contract_id' => $contractId,
            // Track when the row was added (right now)
            'row_created_at' => date('Y-m-d H:i:s'),
        ];


        try {
            DB::table('l_contracts')->insert($output);
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            // dd($output);

            echo "Failed to add " . $jsonPath . " \n";
            return false;
        } catch (PDOException $e) {
            echo "Failed to add " . $jsonPath . " \n";
            return false;
        }
    }

    public static function addErrorRowToDatabase($data, $errorType)
    {
        // 1 = CSV import error
        // 2 = no source_fiscal
        // 3 = contract_value is 0
        // 4 = manual error flag

        if (! isset($data['sourceOrigin'])) {
            $data['sourceOrigin'] = 1;
        }
        
        $defaults = [
            'uuid' => null,
            'ownerAcronym' => '',
            'referenceNumber' => null,
            'contractValue' => 0,
            'sourceYear' => null,
            'sourceQuarter' => null,
            'sourceFiscal' => null,
            'sourceOrigin' => null,
            'sourceCsvFilename' => null,
        ];

        $data = array_merge($defaults, $data);

        $output = [
            'json_id' => $data['uuid'],

            'owner_acronym' => $data['ownerAcronym'],
            'reference_number' => $data['referenceNumber'],
            'contract_value' => $data['contractValue'],

            'source_year' => $data['sourceYear'],
            'source_quarter' => $data['sourceQuarter'],
            'source_fiscal' => $data['sourceFiscal'],
            'source_origin' => $data['sourceOrigin'],
            'source_csv_filename' => $data['sourceCsvFilename'],

            'row_created_at' => date('Y-m-d H:i:s'),
            'gen_is_error' => 1,
            'gen_error_via' => intval($errorType),
        ];

        DB::table('l_contracts')->insert($output);

        try {
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            // dd($output);

            echo "Failed to add error row (query exception) \n";
            return false;
        } catch (PDOException $e) {
            echo "Failed to add error row (PDO exception) \n";
            return false;
        }
    }

    public static function importDepartmentalJsonToDatabase($acronym, $clearOldEntries = 1)
    {

        // Run the operation!
        $startDate = date('Y-m-d H:i:s');
        echo "Starting " . $acronym . " at ". $startDate . " \n\n";

        if ($clearOldEntries) {
            DB::table('l_contracts')->where('owner_acronym', '=', $acronym)->delete();
        }

        // "Output" directory refers to where the generated JSON files are stored
        $jsonDirectory = Paths::getOutputDirectoryForDepartment($acronym);

        if (! file_exists($jsonDirectory)) {
            return false;
        }

        $fileList = array_diff(scandir($jsonDirectory), array('..', '.'));

        $index = 0;
        $successTotal = 0;

        foreach ($fileList as $file) {
            $index++;

            $contractId = $acronym . '-' . str_pad($index, 10, '0', STR_PAD_LEFT);

            if (self::importJsonFileToDatabase($jsonDirectory . '/' . $file, $contractId)) {
                $successTotal++;
            }
        }

        echo "\n...started importing " . $acronym . " at " . $startDate . "\n";
        echo "Imported " . $successTotal . " of " . $index . " contracts.\n";
        echo "Finished " . $acronym . " at ". date('Y-m-d H:i:s') . " \n\n";
    }


    // Reset all the "generated" values related to identifying duplicates and amended contracts
    public static function resetGeneratedValues($acronym = null)
    {
        $updateArray = [
            'gen_is_duplicate' => 0,
            'gen_duplicate_via' => null,
            'gen_duplicate_source_id' => null,
            'gen_is_amendment' => 0,
            'gen_amendment_via' => null,
            'gen_amendment_group_id' => null,
            'gen_effective_start_year' => null,
            'gen_effective_end_year' => null,
            'gen_effective_total_value' => null,
            'gen_effective_yearly_value' => null,
        ];

        if ($acronym) {
            return DB::table('l_contracts')->where('owner_acronym', '=', $acronym)->update($updateArray);
        } else {
            return DB::table('l_contracts')->update($updateArray);
        }
    }

    // Update a list of IDs and mark them as duplicates
    public static function markDuplicateEntries($ownerAcronym, $duplicateRows, $method = 1)
    {
        // Includs the ownerAcronym just to be on the safe side.

        // Remove the first entry (so that it isn't also marked as a duplicate, since at least one entry should stay "valid")
        $sourceId = $duplicateRows->shift();

        $duplicateRows = $duplicateRows->toArray();

        return DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->whereIn('id', $duplicateRows)
            ->update([
                'gen_is_duplicate' => 1,
                'gen_duplicate_via' => $method,
                'gen_duplicate_source_id' => $sourceId,
                ]);
    }


    // Based on a single contract row, check for duplicates in the rest of the database:
    public static function findDuplicateEntries($rowData)
    {
        // For all modes, limit to the same department owner_acronym

        $totalDuplicates = 0;

        // mode 1: same contract_value, same gen_vendor_normalized, same raw_contract_date
        $duplicateRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            ->where('gen_is_duplicate', '=', 0)
            ->where('gen_is_error', '=', 0)
            ->where('contract_value', '=', $rowData['contract_value'])
            ->where('gen_vendor_normalized', '=', $rowData['gen_vendor_normalized'])
            ->where('raw_contract_date', '=', $rowData['raw_contract_date'])
            ->whereNotNull('source_fiscal')
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id');

        if ($duplicateRows->count() > 1) {
            // Then, there's duplicates based on this method.
            $totalDuplicates += self::markDuplicateEntries($rowData['owner_acronym'], $duplicateRows, 1);
        }


        // mode 2: same contract_value, same gen_vendor_normalized, same reference_number, same gen_start_year
        // (in case the raw contract dates are formatted inconsistently or missing)
        $duplicateRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            ->where('gen_is_duplicate', '=', 0)
            ->where('gen_is_error', '=', 0)
            ->where('contract_value', '=', $rowData['contract_value'])
            ->where('gen_vendor_normalized', '=', $rowData['gen_vendor_normalized'])
            ->where('reference_number', '=', $rowData['reference_number'])
            ->where('gen_start_year', '=', $rowData['gen_start_year'])
            ->whereNotNull('source_fiscal')
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id');

        if ($duplicateRows->count() > 1) {
            // Then, there's duplicates based on this method.
            $totalDuplicates += self::markDuplicateEntries($rowData['owner_acronym'], $duplicateRows, 2);
        }

        // mode 3: same gen_vendor_normalized and same reference_number
        // (in case both the raw contract dates and the vendor names are input inconsistently)
        $duplicateRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            ->where('gen_is_duplicate', '=', 0)
            ->where('gen_is_error', '=', 0)
            ->where('contract_value', '=', $rowData['contract_value'])
            ->where('reference_number', '=', $rowData['reference_number'])
            ->whereNotNull('source_fiscal')
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id');

        if ($duplicateRows->count() > 1) {
            // Then, there's duplicates based on this method.
            $totalDuplicates += self::markDuplicateEntries($rowData['owner_acronym'], $duplicateRows, 3);
        }

        return $totalDuplicates;
    }

    public static function findDuplicates($ownerAcronym)
    {

        $totalDuplicates = 0;

        DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->whereNotNull('source_fiscal')
            ->where('gen_is_error', '=', 0)
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('id', 'asc')
            ->select('id')
            ->chunk(100, function ($rows) use (&$totalDuplicates) {
                foreach ($rows as $row) {
                    // Check if it's a duplicate *in here* rather than in the parent query,
                    // in case one iteration will change the values of the next ones:
                    $rowData = (array) DB::table('l_contracts')
                        ->where('id', $row->id)
                        ->where('gen_is_duplicate', '=', 0)
                        ->first();

                    if ($rowData) {
                        $totalDuplicates += self::findDuplicateEntries($rowData);
                    }
                }
            });
            
        return $totalDuplicates;
    }

    public static function markAmendmentEntries($ownerAcronym, $amendmentRows, $method = 1)
    {
        // Includs the ownerAcronym just to be on the safe side.

        // Remove the first entry (so that it isn't also marked as a duplicate, since at least one entry should stay "valid")
        $sourceId = $amendmentRows->shift();

        $amendmentRows = $amendmentRows->toArray();

        // Update the original (sourceId) entry
        DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->where('id', '=', $sourceId)
            ->update([
                'gen_amendment_group_id' => $sourceId,
                ]);

        return DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->whereIn('id', $amendmentRows)
            ->update([
                'gen_is_amendment' => 1,
                'gen_amendment_via' => $method,
                'gen_amendment_group_id' => $sourceId,
                ]);
    }

    public static function findAmendmentEntries($rowData)
    {
        // For all modes, limit to the same department owner_acronym

        $totalAmendments = 0;
        
        // mode 1: same gen_vendor_normalized, same reference_number, different contract_value
        $amendmentRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            // Ensure it's not the exact same row:
            ->where('id', '!=', $rowData['id'])
            // Make sure it's not a duplicate entry
            ->where('gen_is_duplicate', '=', 0)
            // Make sure it's not an error row
            ->where('gen_is_error', '=', 0)
            // Make sure it isn't part of a different amendment group (TODO - review this)
            ->whereNull('gen_amendment_group_id')
            ->whereNotNull('source_fiscal')
            // Make sure it's the same vendor:
            ->where('gen_vendor_normalized', '=', $rowData['gen_vendor_normalized'])

            // This is a bit of a complicated combination, but the resulting SQL is,
            //  and ("reference_number" = ? or ("original_value" = ? and "gen_start_year" = ?))
            // Because of threshold limits (sole source, NAFTA, etc.), we wouldn't want to just match original and contract values without also matching start years (in case completely different contracts have the same values).
            ->where(function ($query) use ($rowData) {
                return $query->where('reference_number', '=', $rowData['reference_number'])
                    ->orWhere(function ($query) use ($rowData) {
                        return $query->where('original_value', '=', $rowData['contract_value'])
                            ->where('gen_start_year', '=', $rowData['gen_start_year']);
                    });
            })
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('contract_value', 'asc')
            // ->orderBy('id', 'asc')
            // ->toSql();
            ->pluck('id');

        if ($amendmentRows->count() > 0) {
            // Just 1 row is enough (since it'll be different than the original row)
            // Add back in the original ID (sorted by source_fiscal then ID in the earlier query in findAmendments)
            $amendmentRows->prepend($rowData['id']);

            $totalAmendments += self::markAmendmentEntries($rowData['owner_acronym'], $amendmentRows, 1);
        }

        return $totalAmendments;
    }

    public static function findAmendments($ownerAcronym)
    {

        $totalAmendments = 0;

        DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->where('gen_is_duplicate', '=', 0)
            ->where('gen_is_error', '=', 0)
            ->whereNotNull('source_fiscal')
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('id', 'asc')
            ->select('id')
            ->chunk(100, function ($rows) use (&$totalAmendments) {
                foreach ($rows as $row) {
                    // Check if it's a duplicate *in here* rather than in the parent query,
                    // in case one iteration will change the values of the next ones:
                    $rowData = (array) DB::table('l_contracts')
                        ->where('id', $row->id)
                        ->whereNull('gen_amendment_group_id')
                        ->first();

                    if ($rowData) {
                        $totalAmendments += self::findAmendmentEntries($rowData);
                    }
                }
            });
            
        return $totalAmendments;
    }

    // Unlike the Paths version,this one gets it from the database:
    public static function getAllDepartmentAcronyms()
    {

        return DB::table('l_contracts')
            ->select(['owner_acronym'])
            ->orderBy('owner_acronym')
            ->distinct()
            ->pluck('owner_acronym')
            ->toArray();
    }

    public static function calculateEffectiveAmendmentValues($rowData)
    {
        // rowData is a collection of rows that all have the same gen_amendment_group_id
        // These are already sorted by ascending source_fiscal

        foreach ($rowData as $row) {
            // Fix for situations where the end year is earlier than the start year
            // use whichever is later of the start year or the source year (when it was published).
            if ($row->gen_end_year < $row->gen_start_year) {
                $row->gen_end_year = $row->gen_start_year;
                if ($row->source_year > $row->gen_end_year) {
                    $row->gen_end_year = $row->source_year;
                }
            }
        }

        // We're using Collection methods here, which are great:
        // https://laravel.com/docs/5.6/collections

        // Step 1: find the earliest and latest years of the contract
        $earliestYear = $rowData->min('gen_start_year');

        // Update: rather than the maximum end year, it should actually be the end year of the *last* row in the (ordered by source_fiscal) array of amendments.
        // In some cases, the contract gets *shortened* from what was originally planned.
        // $latestYear = $rowData->max('gen_end_year');
        $latestYear = $rowData->last()->gen_end_year;

        // Edge cases where the end year is earlier than the start year, use the last one's start year instead:
        if ($rowData->last()->gen_start_year > $rowData->last()->gen_end_year) {
            $latestYear = $rowData->last()->gen_start_year;
        }

        $originalValue = $rowData->min('original_value');
        if (! $originalValue) {
            $originalValue = $rowData->min('contract_value');
        }

        // Step 2: create an array range of each year in this set, and then match each year with the most updated amendment row ID

        $years = range($earliestYear, $latestYear);
        $yearMapping = [];

        $firstRow = 1;
        $genAmendmentGroupId = null;

        foreach ($rowData as $row) {
            // Store this for error tracking later (it's the same for all rows in rowData)
            if (! $genAmendmentGroupId) {
                $genAmendmentGroupId = $row->gen_amendment_group_id;
            }

            foreach ($years as $year) {
                if ($firstRow) {
                    // Use the start_year since this is the beginning
                    // even if the source_year is later (if it was retroactively published)
                    if ($row->gen_start_year <= $year && $row->gen_end_year >= $year) {
                        $yearMapping[$year] = $row->id;
                    }
                } else {
                    // Use the source_year instead of the start_year
                    if ($row->source_year <= $year && $row->gen_end_year >= $year) {
                        $yearMapping[$year] = $row->id;
                    }
                }
            }

            $firstRow = 0;
        }

        // dd($yearMapping);
        // var_dump($yearMapping);
        // array:3 [
        //   2010 => 1251303
        //   2011 => 1250608
        //   2012 => 1250608
        // ]

        // Step 3: loop through rows again and set effective start and end years
        $cumulativeTotal = 0;
        $firstRow = 1;
        $rowIdsToUpdate = [];

        foreach ($rowData as $row) {
            $effectiveStartYear = null;
            $effectiveEndYear = null;

            foreach ($yearMapping as $year => $rowId) {
                if ($rowId == $row->id) {
                    // If they match, update the effective start and end years
                    // echo "Match: " . $row->id . " for " . $year . "\n";
                    if ($effectiveStartYear == null || $year < $effectiveStartYear) {
                        $effectiveStartYear = $year;
                    }
                    if ($effectiveEndYear == null || $year > $effectiveEndYear) {
                        $effectiveEndYear = $year;
                    }
                } else {
                    // echo "No match for: " . $row->id . " for " . $year . "\n";
                }
            }

            if ($effectiveStartYear == null || $effectiveEndYear == null) {
                // If this row ID isn't in the yearMapping array, skip to the next row.
                // echo "Skipping... \n";
                continue;
            }

            $rowIdsToUpdate[] = $row->id;
            // echo "here for " . $row->id . "\n";

            $row->gen_effective_start_year = $effectiveStartYear;
            $row->gen_effective_end_year = $effectiveEndYear;

            // Effective total value is, the theoretical yearly value of the contract over the originally planned start and end years
            if ($firstRow) {
                $theoreticalYearlyValue = $row->contract_value / ($row->gen_end_year - $row->gen_start_year + 1);
            } else {
                // Update 2021-04-03: Subtract the cumulative total from the latest contract value here, before averaging across the years between the row's end year and source year
                $theoreticalYearlyValue = ($row->contract_value - $cumulativeTotal)  / ($row->gen_end_year - $row->source_year + 1);
            }
            

            // dd($effectiveEndYear);

            $row->gen_effective_total_value = $theoreticalYearlyValue * ($effectiveEndYear - $effectiveStartYear + 1);
            $row->gen_effective_yearly_value = $row->gen_effective_total_value / ($effectiveEndYear - $effectiveStartYear + 1);

            $cumulativeTotal += $row->gen_effective_total_value;

            $firstRow = 0;
        }

        $updatesSaved = 0;
        // Update the rows in the database:
        foreach ($rowData as $index => $row) {
            $isFinalValue = 0;
            if ($index == count($rowData) - 1) {
                $isFinalValue = 1;
            }
            

            // Make sure there are actually changes
            if (in_array($row->id, $rowIdsToUpdate)) {
                DB::table('l_contracts')
                    ->where('owner_acronym', '=', $row->owner_acronym)
                    ->where('id', '=', $row->id)
                    ->update([
                        'gen_effective_start_year' => $row->gen_effective_start_year,
                        'gen_effective_end_year' => $row->gen_effective_end_year,
                        'gen_effective_total_value' => $row->gen_effective_total_value,
                        'gen_effective_yearly_value' => $row->gen_effective_yearly_value,
                        'gen_original_value' => $originalValue,
                        'gen_is_most_recent_value' => $isFinalValue,
                        ]);
                $updatesSaved = 1;
                // echo "Updated " . $row->id . "\n";
            } else {
                // Update the effective total and yearly values, but not the start and end years
                // these are amendments that were overridden by other amendments in the same year.
                DB::table('l_contracts')
                    ->where('owner_acronym', '=', $row->owner_acronym)
                    ->where('id', '=', $row->id)
                    ->update([
                        'gen_effective_total_value' => 0,
                        'gen_effective_yearly_value' => 0,
                        ]);
            }
        }

        if ($updatesSaved) {
            return true;
        } else {
            echo "No updates for gen_amendment_group_id " . $genAmendmentGroupId . "\n";
            return false;
        }
    }

    public static function updateEffectiveAmendmentValues($ownerAcronym)
    {

        $totalUpdates = 0;

        DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->where('gen_is_duplicate', '=', 0)
            ->where('gen_is_error', '=', 0)
            // Useful for testing purposes:
            // ->where('gen_amendment_group_id', '=', 1251964)
            ->whereNotNull('gen_amendment_group_id')
            ->whereNotNull('gen_start_year')
            ->whereNotNull('gen_end_year')
            ->whereNotNull('source_fiscal')
            ->orderBy('gen_amendment_group_id', 'asc')
            ->select('gen_amendment_group_id')
            ->distinct()
            ->chunk(100, function ($rows) use (&$totalUpdates) {
                foreach ($rows as $row) {
                    $rowData = DB::table('l_contracts')
                        ->where('gen_amendment_group_id', '=', $row->gen_amendment_group_id)
                        ->orderBy('source_fiscal', 'asc')
                        ->orderBy('contract_value', 'asc')
                        ->whereNotNull('source_fiscal')
                        ->get();

                    if ($rowData) {
                        if (self::calculateEffectiveAmendmentValues($rowData)) {
                            $totalUpdates++;
                        }
                    }
                }
            });
            
        return $totalUpdates;
    }

    public static function updateEffectiveRegularValues($ownerAcronym)
    {

        DB::table('l_contracts')
            ->where('owner_acronym', '=', $ownerAcronym)
            ->where('gen_is_duplicate', '=', 0)
            ->where('gen_is_error', '=', 0)
            // Find all contracts that *do not* have amendments
            ->whereNull('gen_amendment_group_id')
            ->whereNotNull('gen_start_year')
            ->whereNotNull('gen_end_year')
            ->whereNotNull('source_fiscal')
            ->orderBy('source_fiscal', 'asc')
            ->orderBy('id', 'asc')
            ->chunk(100, function ($rows) use (&$totalAmendments) {
                foreach ($rows as $row) {
                    // Fix for situations where the end year is earlier than the start year
                    // use whichever is later of the start year or the source year (when it was published).
                    if ($row->gen_end_year < $row->gen_start_year) {
                        $row->gen_end_year = $row->gen_start_year;
                        if ($row->source_year > $row->gen_end_year) {
                            $row->gen_end_year = $row->source_year;
                        }
                    }

                    $originalValue = $row->original_value;
                    if (! $originalValue) {
                        $originalValue = $row->contract_value;
                    }
                    
                    DB::table('l_contracts')
                        ->where('owner_acronym', '=', $row->owner_acronym)
                        ->where('id', '=', $row->id)
                        ->update([
                            'gen_effective_start_year' => $row->gen_start_year,
                            'gen_effective_end_year' => $row->gen_end_year,
                            'gen_effective_total_value' => $row->contract_value,
                            'gen_effective_yearly_value' => $row->contract_value / ($row->gen_end_year - $row->gen_start_year + 1),
                            'gen_original_value' => $originalValue,
                            // For rows without amendments, the current value is also the final / most recent value:
                            'gen_is_most_recent_value' => 1,
                        ]);
                }
            });
    }

    // Deprecated by the renormalizeVendorNames function below
    public static function updateCleanVendorNames($updateExport = 1)
    {

        $vendorData = VendorData::getInstance();

        foreach ($vendorData->vendorTable as $rawName => $cleanName) {
            echo "Replacing <" . $rawName . "> with <" . $cleanName . ">\n";

            // Update the source contracts table
            // This may not do as precise of matches as the original vendor cleanup function - it'll catch new additions to the table, but not updates to the $charactersToRemove array.
            DB::table('l_contracts')
                ->where('gen_vendor_clean', '=', VendorData::cleanupVendorName($rawName))
                ->update([
                    'gen_vendor_normalized' => $cleanName,
                    ]);

            if ($updateExport) {
                // DB::table('exports_v1')
                //     ->where('vendor_clean', '=', $rawName)
                //     ->update([
                //         'vendor_clean' => $cleanName,
                //         ]);
                DB::table('exports_v1')
                    ->where('gen_vendor_clean', '=', VendorData::cleanupVendorName($rawName))
                    ->update([
                        'gen_vendor_normalized' => $cleanName,
                        ]);
            }
        }
    }

    public static function renormalizeVendorNames($includeExportsV1 = 0, $includeExportsV2 = 0)
    {

        $vendorData = VendorData::getInstance();
        
        foreach ($vendorData->vendorTable as $rawName => $cleanName) {
            echo "Replacing <" . $rawName . "> with <" . $cleanName . ">\n";

            DB::table('l_contracts')
                ->where('gen_vendor_clean', '=', $rawName)
                ->update([
                    'gen_vendor_normalized' => $cleanName,
                    ]);
            
            if ($includeExportsV1) {
                DB::table('exports_v1')
                ->where('vendor_clean', '=', $rawName)
                ->update([
                    'vendor_normalized' => $cleanName,
                    ]);
            }
            if ($includeExportsV2) {
                DB::table('exports_v2')
                ->where('vendor_clean', '=', $rawName)
                ->update([
                    'vendor_normalized' => $cleanName,
                    ]);
            }
        }
    }

    public static function findUpdatedVendorNames($echoCsv = 0)
    {

        $vendorData = VendorData::getInstance();
        $matches = [];
        $output = [];
        
        foreach ($vendorData->vendorTable as $cleanName => $normalizedName) {
            // dd($cleanName);

            $rawNames = DB::table('l_contracts')
                ->where('gen_vendor_clean', '=', $cleanName)
                ->select('vendor_name')
                ->distinct()
                ->pluck('vendor_name');

            $matches[$cleanName] = [
                'cleanName' => $cleanName,
                'normalizedName' => $normalizedName,
                'matches' => [],
            ];

            foreach ($rawNames as $rawName) {
                $matches[$cleanName]['matches'][] = [
                    'rawName' => $rawName,
                    'cleanNameV2' => VendorData::cleanupVendorNameV2($rawName),
                ];
            }

            // $matches[$cleanName] = $rawNames->toArray();
        }

        // var_export($matches);

        foreach ($matches as $originalName => $data) {
            // Add the original entry (which may not show up in the DB)
            $output[VendorData::cleanupVendorNameV2($originalName)] = VendorData::cleanupVendorNameV2($data['normalizedName']);

            // Add any entries from the database lookup
            foreach ($data['matches'] as $match) {
                $output[$match['cleanNameV2']] = VendorData::cleanupVendorNameV2($data['normalizedName']);
            }
        }

        if ($echoCsv) {
            $csvOutput = "Parent company,Company name\n";
            foreach ($output as $cleanName => $normalizedName) {
                $csvOutput .= "$normalizedName,$cleanName\n";
            }
            echo $csvOutput;
            return;
        } else {
            return $output;
        }
    }

    public static function renormalizeOwnerNames()
    {

        foreach (Miscellaneous::$ownerAcronymMapping as $originalName => $updatedName) {
            echo "Replacing <" . $originalName . "> with <" . $updatedName . ">\n";

            DB::table('l_contracts')
                ->where('owner_acronym', '=', $originalName)
                ->update([
                    'owner_acronym' => $updatedName,
                    ]);
        }
    }

    public static function checkForDataErrors()
    {

        echo "Checking for missing source_fiscal\n";

        DB::table('l_contracts')
            ->whereNull('source_fiscal')
            ->where('gen_is_error', '=', 0)
            ->update([
                'gen_is_error' => 1,
                'gen_error_via' => 2,
                ]);
        
        echo "Checking for empty contract_value\n";

        DB::table('l_contracts')
            ->where('contract_value', '=', 0)
            ->where('gen_is_error', '=', 0)
            ->update([
                'gen_is_error' => 1,
                'gen_error_via' => 3,
                ]);
        
        self::manuallyFlagErrorRows();

        echo "Done error checking.\n";
    }

    public static function manuallyFlagErrorRows() {

        $errorContractIds = [
            'pspc-0000101859',
        ];

        $count = 0;
        foreach($errorContractIds as $errorContractId) {
            DB::table('l_contracts')
            ->where('gen_contract_id', '=', $errorContractId)
            ->update([
                'gen_is_error' => 1,
                'gen_error_via' => 4,
                ]);
            
                $count++;
        }

        echo "Flagged $count manual error rows.\n";

    }


}
