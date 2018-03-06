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

        // echo $jsonPath . "\n";

        $json = json_decode(file_get_contents($jsonPath), 1);

        // Clean up empty string entries
        $json = array_map(function ($entry) {
            if ($entry === '') {
                $entry = null;
            }
            return $entry;
        }, $json);

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
            'source_origin' => 1,
            'gen_start_year' => $json['startYear'],
            'gen_end_year' => $json['endYear'],
            'gen_vendor_clean' => $json['vendorClean'],
            'gen_contract_id' => $contractId,
        ];


        try {
            DB::table('contracts')->insert($output);
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
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
            DB::table('contracts')->where('owner_acronym', '=', $acronym)->delete();
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
}
