<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\DbOps;
use Illuminate\Console\Command;

class UpdateMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'department:metadata {action : either reset, duplicates, or amendments.} {department=all : either an acronym, or all }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the metadata associated with a department\'s duplicate and amendment entries.';

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
        $action = $this->argument('action');
        

        $departmentArgument = $this->argument('department');

        $departmentList = DbOps::getAllDepartmentAcronyms();

        // dd($departmentList);

        $startDate = date('Y-m-d H:i:s');
        echo "Starting '" . $action . "' update at ". $startDate . " \n";


        if ($action == 'cleannames') {
            // Only happens once across all departments:
            DbOps::updateCleanVendorNames();
        } else {
            // Department-specific actions:

            if ($departmentArgument != 'all') {
                if (in_array($departmentArgument, $departmentList)) {
                    // Only apply operations to the single selected department
                    $departmentList = [$departmentArgument];
                } else {
                    // Requested a department that doesn't exist:
                    $this->error('The requested department (' . $departmentArgument . ') does not exist.');
                    exit();
                }
            }

            foreach ($departmentList as $department) {
                if ($action == 'reset') {
                    DbOps::resetGeneratedValues($department);
                } else if ($action == 'duplicates') {
                    DbOps::findDuplicates($department);
                } else if ($action == 'amendments') {
                    DbOps::findAmendments($department);
                } else if ($action == 'amendmentvalues') {
                    DbOps::updateEffectiveAmendmentValues($department);
                } else if ($action == 'regularvalues') {
                    DbOps::updateEffectiveRegularValues($department);
                } else if ($action == 'all') {
                    DbOps::resetGeneratedValues($department);
                    DbOps::findDuplicates($department);
                    DbOps::findAmendments($department);
                    DbOps::updateEffectiveAmendmentValues($department);
                    DbOps::updateEffectiveRegularValues($department);
                }

                echo "  finished " . $department . " at " . date('Y-m-d H:i:s'). "\n";
            }
        }
        


        echo "\n\n...started '" . $action . "' update at " . $startDate . "\n";
        echo "Finished '" . $action . "' update at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
