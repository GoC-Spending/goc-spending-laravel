<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\CsvOps;
use App\DbOps;
use App\AnalysisOps;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportCsvAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports CSV aggregate analysis files to the goc-spending-analysis folder.';

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
        echo "Starting to export aggregate CSV files at ". $startDate . " \n";

        AnalysisOps::generateAnalysisCsvFiles();

        echo "\n\n...started exporting aggregate CSV files at " . $startDate . "\n";
        echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
