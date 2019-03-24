<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\CsvOps;
use App\DbOps;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DownloadCsvContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads the the open.canada.ca Proactive Disclosure of Contracts dataset into the goc-spending-csv directory.';

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
        echo "Starting CSV download at ". $startDate . " \n";

        CsvOps::downloadLatestCsvFile();

        echo "\n\n...started CSV download at " . $startDate . "\n";
        echo "Finished download at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
