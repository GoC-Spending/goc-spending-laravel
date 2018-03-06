<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use Illuminate\Console\Command;

class ParseAllDepartments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'department:parseall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the downloaded contract HTML for all departments.';

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
        echo "Starting to parse all departments at ". $startDate . " \n";

        $departmentList = Paths::getAllDepartmentAcronyms();

        foreach ($departmentList as $department) {
            $departmentClass = 'App\\DepartmentHandlers\\' . ucfirst(strtolower($department)) . 'Handler';

            // Check to see if the DepartmentHandler exists. If not, bail gracefully!
            if (! class_exists($departmentClass)) {
                $this->error('No department handler for "' . $department . '".');
                continue;
            }

            // Check to see if the data directory exists. If not, bail gracefully!
            if (! is_dir(Paths::getSourceDirectoryForDepartment($department))) {
                $this->error('No data folder for "' . $department . '". Try running department:fetch for it first.');
                continue;
            }

            echo "\n";

            $departmentHandler = new $departmentClass;

            $departmentHandler->parseAll();
        }

        echo "\n\n...started parsing all departments at " . $startDate . "\n";
        echo "Finished parsing " . count($departmentList) . " departments at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
