<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\CsvOps;
use App\DbOps;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCsvContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the open.canada.ca Proactive Disclosure of Contracts dataset into the Laravel database.';

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

        $startDate = date('Y-m-d H:i:s');
        echo "Starting to import CSV rows at ". $startDate . " \n";

        $totalRows = 0;
        $successTotal = 0;

        $csvFilepath = CsvOps::getLatestCsvFile();

        $csvFilename = pathinfo($csvFilepath, PATHINFO_FILENAME);
        
        // Clear old entries from the database.
        // source_origin = 2 is the CSV file
        DB::table('l_contracts')->where('source_origin', '=', 2)->delete();



        $row = 1;
        if (($handle = fopen($csvFilepath, "r")) !== false) {
            while (($data = fgetcsv($handle, 0, ",")) !== false) {
                if ($row != 1) {
                    // Skip the header row

                    try {
                        $output = CsvOps::rowToArray($data, $csvFilename);
                    } catch (\ErrorException $e) {
                        echo "Failed to convert row $row\n";
                        var_dump($data);
                        continue;
                    }
                    
                    if ($output) {
                        $contractId = $output['ownerAcronym'] . '-csv-' . str_pad($row, 10, '0', STR_PAD_LEFT);

                        if (DbOps::importJsonDataToDatabase($output, $contractId, 'csv:' . $row)) {
                            $successTotal++;
                        }
                    }

                    $totalRows++;
                }
            
                $row += 1;
            }
            fclose($handle);
        }

        echo "\n\n...started importing CSV rows at " . $startDate . "\n";
        echo "Finished parsing " . $successTotal . " of " . $totalRows . " rows at ". date('Y-m-d H:i:s') . " \n\n";

        echo "\n\nNormalizing owner acronyms\n";
        DbOps::renormalizeOwnerNames();

        echo "Finished normalizing owner acronyms at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
