<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchDepartment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'department:fetch {acronym : The acronym of the department to scrape.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the contract HTML for a department.';

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

        $departmentHandler = new $departmentClass;
        $departmentHandler->fetch();
    }
}
