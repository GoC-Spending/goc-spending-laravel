<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\DbOps;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class ExportDatabaseV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the exports_v2 database table.';

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
        echo "Starting database export at ". $startDate . " \n";

        // Remove existing export rows:
        DB::table('exports_v2')->truncate();

        $totalRows = 0;

        // Across all departments,
        // as long as there's a source_fiscal and it's not a duplicate entry.
        DB::table('exports_v1')
            ->orderBy('owner_acronym', 'asc')
            ->orderBy('source_fiscal', 'asc')
            ->chunk(100, function ($rows) use (&$totalRows) {
                foreach ($rows as $row) {
                    // Make sure that start/end years exist
                    if ($row->effective_start_year && $row->effective_end_year) {
                        // For each year between the start and end year (inclusive), add a row:
                        foreach (range($row->effective_start_year, $row->effective_end_year) as $effectiveYear) {
                            DB::table('exports_v2')->insert([

                                'vendor_clean' => $row->vendor_clean,
                                'owner_acronym' => $row->owner_acronym,

                                'yearly_value' => $row->effective_yearly_value,
                                'effective_year' => $effectiveYear,

                                'source_fiscal' => $row->source_fiscal,
                                'source_year' => $row->source_year,
                                'source_quarter' => $row->source_quarter,
                                'source_origin' => $row->source_origin,

                                'is_amendment' => $row->is_amendment,
                                'amendment_group_id' => $row->amendment_group_id,
                                'reference_number' => $row->reference_number,
                                'object_code' => $row->object_code,
                                'description' => $row->description,
                                'extra_description' => $row->extra_description,
                                'comments' => $row->comments,
                                ]);
                        }
                    }

                    

                    $totalRows++;
                }

                if ($totalRows % 1000 == 0) {
                    echo "  " . $totalRows . "\n";
                }
            });

        echo "\n\n...started database export update at " . $startDate . "\n";
        echo "Finished database export at ". date('Y-m-d H:i:s') . " \n\n";
    }
}
