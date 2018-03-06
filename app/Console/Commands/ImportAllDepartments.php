<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\DbOps;
use Illuminate\Console\Command;

class ImportAllDepartments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'department:importall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the parsed JSON files into the Laravel database.';

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
        echo "Starting to import all departments at ". $startDate . " \n";

        $departmentList = Paths::getAllDepartmentAcronyms();

        foreach ($departmentList as $department) {
            DbOps::importDepartmentalJsonToDatabase($department);
        }

        echo "\n\n...started importing all departments at " . $startDate . "\n";
        echo "Finished importing " . count($departmentList) . " departments at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
