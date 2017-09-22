<?php

namespace App\Console\Commands;

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

        $departmentHandler = new $departmentClass;
        $departmentHandler->parse();
    }
}
