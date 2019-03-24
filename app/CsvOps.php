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

    // CSV value => scraper value
    public static $ownerAcronymMapping = [
        'feddevontario' => 'feddev',
        'statcan' => 'stats',
        'pwgsc' => 'pspc',
        'jus' => 'just',
        'infc' => 'infra',
        'aandc' => 'inac',
        'aafc' => 'agr',
    ];

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

    public static function rowToArray($rowData, $csvFilename)
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

            if (! $data['referenceNumber']) {
                $data['referenceNumber'] = $data['csvReferenceNumber'];
            }

            $data = self::csvFiscalHandling($data);
            $data = self::csvOwnerAcronymHandling($data);

            $data = DepartmentHandler::parseSingleData($data);

            $data['uuid'] = $data['ownerAcronym'] . '-' . $data['referenceNumber'];

            $data['sourceOrigin'] = 2;
            $data['sourceCsvFilename'] = $csvFilename;

            return $data;
        } else {
            // dd($data);
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

        $data['sourceYear'] = Parsers::xpathReturnSingle($sourceFiscal, '/((?:19|20)\d{2})\D/');

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

        if (array_key_exists($data['ownerAcronym'], self::$ownerAcronymMapping)) {
            $data['ownerAcronym'] = self::$ownerAcronymMapping[$data['ownerAcronym']];
        }
        
        return $data;
    }

    // Gets the CSV file from the open.canada.ca website, since it's too large to track in GitHub.
    // This may take several minutes to run.
    // Usage is via Artisan,
    // php artisan csv:download
    public static function downloadLatestCsvFile()
    {

        $datasetpath = env('CSV_DATASET_PATH');

        if (! $datasetpath) {
            echo "Error: you need to set CSV_DATASET_PATH in your .env file.";
            return false;
        }

        $filepath = storage_path() . '/' . env('STORAGE_RELATIVE_CSV_FOLDER');
        // The online CSV file is updated daily, so if we have one from today it's okay to replace it:
        $filename = date('Y-m-d') . '-contracts.csv';

        if (file_exists($filepath.$filename)) {
            // Remove an existing entry from today to avoid stream errors or confusion below:
            unlink($filepath.$filename);
        }

        // Stream-file saving, to save memory
        // Thanks to
        // https://stackoverflow.com/a/3938551/756641
        file_put_contents($filepath . $filename, fopen("$datasetpath", 'r'));
    }

    public static function getLatestCsvFile()
    {

        $folderpath = storage_path() . '/' . env('STORAGE_RELATIVE_CSV_FOLDER');

        // Thanks to
        // https://stackoverflow.com/a/2667105/756641
        // Gets all CSV files, and sorts in descending date order (awesome!)
        $files = glob("$folderpath*.csv");
        usort($files, function ($a, $b) {
            return filemtime($a) < filemtime($b);
        });
        
        if ($files) {
            return $files[0];
        } else {
            echo "No CSV files could be found.";
            return false;
        }
    }
}
