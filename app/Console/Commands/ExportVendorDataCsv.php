<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use Illuminate\Console\Command;

use App\VendorData;

class ExportVendorDataCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:vendordata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports existing VendorData.php entries to a CSV file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "Exporting vendor entries. \n";

        $vendorArray = VendorData::$vendors;

        $vendorKeys = array_keys($vendorArray);

        sort($vendorKeys, SORT_NATURAL);

        $filepath = Paths::getVendorDataDirectory() . "vendor_data.csv";

        $outputArray = [];

        foreach ($vendorKeys as $parent) {
            $outputArray[strtoupper(trim($parent))] = [];
        }

        foreach ($vendorArray as $parent => $childArray) {
            foreach ($childArray as $child) {
                $outputArray[strtoupper(trim($parent))][] = VendorData::cleanupVendorName($child);
            }
        }

        foreach ($outputArray as $parent => &$childArray) {
            sort($childArray, SORT_NATURAL);
            $childArray = array_unique($childArray, SORT_STRING);
        }

        // dd($outputArray);

        $outputCsvString = "Parent company,Company name\n";

        foreach ($outputArray as $parent => $childArray) {
            foreach ($childArray as $child) {
                $outputCsvString .= $parent . "," . VendorData::cleanupVendorName($child) . "\n";
            }
        }

        file_put_contents($filepath, $outputCsvString);

        echo "Finished exporting vendor entries. \n";
    }
}
