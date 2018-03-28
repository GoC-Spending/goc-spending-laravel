<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\DbOps;
use Illuminate\Console\Command;

class UpdateAllMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'department:allmetadata {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all metadata associated with departments\' duplicate and amendment entries.';

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
        $reset = $this->option('reset');

        $metadataStatusPath = storage_path() . "/" . env('STORAGE_METADATA_STATUS', 'metadata_status.json');
        $metadataStatus = [];

        if ($reset == false) {
            if (file_exists($metadataStatusPath)) {
                // pass;
                $fileInput = json_decode(file_get_contents($metadataStatusPath), 1);
                if (is_array($fileInput)) {
                    $metadataStatus = $fileInput;
                }
            }
        }
        // Otherwise, start with a fresh array.



        $departmentList = DbOps::getAllDepartmentAcronyms();



        $startDate = date('Y-m-d H:i:s');
        echo "Starting full metadata update at ". $startDate . " \n";


        foreach ($departmentList as $department) {
            if (isset($metadataStatus[$department]['completed']) && $metadataStatus[$department]['completed']) {
                // Department has already been completed
                echo " " . $department . " was already completed.\n";
                continue;
            }

            // Otherwise, note that you're starting it
            $metadataStatus[$department] = [
                'started' => date('Y-m-d H:i:s'),
            ];
            file_put_contents($metadataStatusPath, json_encode($metadataStatus, JSON_PRETTY_PRINT));


            // Do all database operations here:
            DbOps::resetGeneratedValues($department);
            DbOps::findDuplicates($department);
            DbOps::findAmendments($department);
            DbOps::updateEffectiveAmendmentValues($department);
            DbOps::updateEffectiveRegularValues($department);


            // Update again that it finished successfully
            $metadataStatus[$department]['completed'] = date('Y-m-d H:i:s');
            file_put_contents($metadataStatusPath, json_encode($metadataStatus, JSON_PRETTY_PRINT));

            echo "  finished " . $department . " at " . date('Y-m-d H:i:s'). "\n";
        }


        

        

        echo "\n\n...started full metadata update at " . $startDate . "\n";
        echo "Finished full metadata update at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
