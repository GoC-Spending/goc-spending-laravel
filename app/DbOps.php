<?php
namespace App;

use App\Helpers\Cleaners;
use App\Helpers\ContractDataProcessors;
use App\Helpers\Parsers;
use App\Helpers\Paths;
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

        $output = [
            'json_id' => $json['uuid'],
            'vendor_name' => $json['vendorName'],
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
            'gen_start_year' => $json['startYear'],
            'gen_end_year' => $json['endYear'],
            'gen_vendor_clean' => $json['vendorClean'],
            'gen_contract_id' => $contractId,
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

        // mode 1: same contract_value, same gen_vendor_clean, same raw_contract_date
        $duplicateRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            ->where('gen_is_duplicate', '=', 0)
            ->where('contract_value', '=', $rowData['contract_value'])
            ->where('gen_vendor_clean', '=', $rowData['gen_vendor_clean'])
            ->where('raw_contract_date', '=', $rowData['raw_contract_date'])
            ->orderBy('id')
            ->pluck('id');

        if ($duplicateRows->count() > 1) {
            // Then, there's duplicates based on this method.
            $totalDuplicates += self::markDuplicateEntries($rowData['owner_acronym'], $duplicateRows, 1);
        }


        // mode 2: same contract_value, same gen_vendor_clean, same reference_number, same gen_start_year
        // (in case the raw contract dates are formatted inconsistently or missing)
        $duplicateRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            ->where('gen_is_duplicate', '=', 0)
            ->where('contract_value', '=', $rowData['contract_value'])
            ->where('gen_vendor_clean', '=', $rowData['gen_vendor_clean'])
            ->where('reference_number', '=', $rowData['reference_number'])
            ->where('gen_start_year', '=', $rowData['gen_start_year'])
            ->orderBy('id')
            ->pluck('id');

        if ($duplicateRows->count() > 1) {
            // Then, there's duplicates based on this method.
            $totalDuplicates += self::markDuplicateEntries($rowData['owner_acronym'], $duplicateRows, 2);
        }

        // mode 3: same contract_value and same reference_number
        // (in case both the raw contract dates and the vendor names are input inconsistently)
        $duplicateRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            ->where('gen_is_duplicate', '=', 0)
            ->where('contract_value', '=', $rowData['contract_value'])
            ->where('reference_number', '=', $rowData['reference_number'])
            ->orderBy('id')
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
        
        // mode 1: same gen_vendor_clean, same reference_number, different contract_value
        $amendmentRows = DB::table('l_contracts')
            ->where('owner_acronym', '=', $rowData['owner_acronym'])
            // Ensure it's not the exact same row:
            ->where('id', '!=', $rowData['id'])
            // Make sure it's not a duplicate entry
            ->where('gen_is_duplicate', '=', 0)
            // Make sure it isn't part of a different amendment group (TODO - review this)
            ->whereNull('gen_amendment_group_id')
            // Make sure it's the same vendor:
            ->where('gen_vendor_clean', '=', $rowData['gen_vendor_clean'])

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
            ->orderBy('id', 'asc')
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
}
