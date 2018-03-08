<?php
namespace App;

use App\DepartmentHandlers\DepartmentHandler;
use App\Helpers\Cleaners;
use App\Helpers\ContractDataProcessors;
use App\Helpers\Parsers;
use App\Helpers\Paths;
use App\VendorData;
use GuzzleHttp\Client;
use XPathSelector\Selector;
use Illuminate\Support\Facades\DB;

class CsvOps
{

    public static $rowMapping = [
        'vendorName' => 2,
        'referenceNumber' => 1,
        'contractDate' => 3,
        'description' => 5,
        'extraDescription' => 14,
        'objectCode' => 4,
        'contractPeriodStart' => 7,
        'deliveryDate' => 8,
        'originalValue' => 10,
        'contractValue' => 9,
        'comments' => 12,
        'ownerAcronym' => 31,
        'sourceYear' => '',
        'sourceQuarter' => '',
        'sourceFiscal' => 30,
        'csvReferenceNumber' => 0,
    ];

    public static function rowToArray($rowData)
    {

        $data = [];

        foreach (self::$rowMapping as $key => $index) {
            if (isset($rowData[$index])) {
                $data[$key] = $rowData[$index];
            }
        }

        $data = array_merge(DepartmentHandler::$rowParams, $data);

        // Make sure there's really data here!
        if ($data['vendorName'] && $data['contractValue'] && $data['ownerAcronym']) {
            $data = ContractDataProcessors::cleanParsedArray($data);

            $data = self::csvFiscalHandling($data);
            $data = self::csvOwnerAcronymHandling($data);

            $data = DepartmentHandler::parseSingleData($data);

            $data['uuid'] = $data['ownerAcronym'] . '-' . $data['referenceNumber'];

            $data['sourceOrigin'] = 2;

            return $data;
        } else {
            return [];
        }
    }

    public static function csvFiscalHandling($data)
    {

        // Assumes that a value has already been stored in $data['sourceFiscal']
        $sourceFiscal = $data['sourceFiscal'];
        if (! $sourceFiscal) {
            $sourceFiscal = $data['csvReferenceNumber'];
        }

        $data['sourceYear'] = Parsers::xpathReturnSingle($sourceFiscal, '/([0-9]{4})/');

        $data['sourceQuarter'] = Parsers::xpathReturnSingle($sourceFiscal, '/Q([0-9])/');

        // Reset sourceFiscal, which gets recreated in ContractDataProcessors::generateAdditionalMetadata
        $data['sourceFiscal'] = '';
        unset($data['csvReferenceNumber']);

        return $data;
    }

    public static function csvOwnerAcronymHandling($data)
    {

        if ($data['ownerAcronym']) {
            $data['ownerAcronym'] = explode('-', $data['ownerAcronym'])[0];
        }
        
        return $data;
    }
}
