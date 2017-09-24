<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use Illuminate\Console\Command;

class ParseDepartment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'department:parse {acronym : The acronym of the department to parse.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the downloaded contract HTML for a department.';

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
        $department = $this->argument('acronym');

        $departmentClass = 'App\\DepartmentHandlers\\' . ucfirst(strtolower($department)) . 'Handler';

        // Check to see if the DepartmentHandler exists. If not, bail gracefully!
        if (! class_exists($departmentClass)) {
            $this->error('No department handler for that department. Check if you’ve typo’ed the department acronym.');
            return;
        }

        // Check to see if the data directory exists. If not, bail gracefully!
        if (! is_dir(Paths::getSourceDirectoryForDepartment($department))) {
            $this->error('No data folder for that department. Try running department:fetch for it first, or check if you’ve typo’ed the department acronym.');
            return;
        }

        $departmentHandler = new $departmentClass;

        $departmentHandler->parseAll();
    }
}
