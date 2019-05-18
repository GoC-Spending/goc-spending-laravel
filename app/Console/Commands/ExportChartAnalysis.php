<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\CsvOps;
use App\DbOps;
use App\AnalysisOps;
use App\ChartOps;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportChartAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chart:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports the website analysis page with charts generated from database data.';

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
        echo "Starting to export the analysis page at ". $startDate . " \n";

        ChartOps::saveAnalysisTemplate();

        echo "\n\n...started exporting the analysis page at " . $startDate . "\n";
        echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
