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
    protected $signature = 'department:metadata {action : either reset, duplicates, or amendments.}';

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
        $departmentList = Paths::getAllDepartmentAcronyms();

        $startDate = date('Y-m-d H:i:s');
        echo "Starting update at ". $startDate . " \n";


        if ($action == 'reset') {
            foreach ($departmentList as $department) {
                DbOps::resetGeneratedValues($department);
                echo "  finished " . $department . " at " . date('Y-m-d H:i:s'). "\n";
            }
        } else if ($action == 'duplicates') {
            foreach ($departmentList as $department) {
                DbOps::findDuplicates($department);
                echo "  finished " . $department . " at " . date('Y-m-d H:i:s'). "\n";
            }
        } else if ($action == 'amendments') {
            foreach ($departmentList as $department) {
                DbOps::findAmendments($department);
                echo "  finished " . $department . " at " . date('Y-m-d H:i:s'). "\n";
            }
        } else if ($action == 'all') {
            foreach ($departmentList as $department) {
                DbOps::resetGeneratedValues($department);
                DbOps::findDuplicates($department);
                DbOps::findAmendments($department);
                echo "  finished " . $department . " at " . date('Y-m-d H:i:s') . "\n";
            }
        }

        

        

        echo "\n\n...started update at " . $startDate . "\n";
        echo "Finished update at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
