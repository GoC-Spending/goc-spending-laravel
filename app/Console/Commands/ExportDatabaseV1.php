<?php

namespace App\Console\Commands;

use App\Helpers\Paths;
use App\DbOps;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class ExportDatabaseV1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:v1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the exports_v1 database table.';

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
        DB::table('exports_v1')->truncate();

        $totalRows = 0;

        // Across all departments,
        // as long as there's a source_fiscal and it's not a duplicate entry.
        DB::table('l_contracts')
            ->where('gen_is_duplicate', '=', 0)
            ->whereNotNull('source_fiscal')
            ->orderBy('owner_acronym', 'asc')
            ->orderBy('source_fiscal', 'asc')
            ->chunk(100, function ($rows) use (&$totalRows) {
                foreach ($rows as $row) {
                    DB::table('exports_v1')->insert([

                        'vendor_clean' => $row->gen_vendor_clean,
                        'vendor_normalized' => $row->gen_vendor_normalized,
                        'owner_acronym' => $row->owner_acronym,
                        'effective_total_value' => $row->gen_effective_total_value,
                        'effective_yearly_value' => $row->gen_effective_yearly_value,
                        'original_value' => $row->gen_original_value,
                        'effective_start_year' => $row->gen_effective_start_year,
                        'effective_end_year' => $row->gen_effective_end_year,
                        'source_fiscal' => $row->source_fiscal,
                        'source_year' => $row->source_year,
                        'source_quarter' => $row->source_quarter,
                        'source_origin' => $row->source_origin,
                        'is_most_recent_value' => intval($row->gen_is_most_recent_value),
                        'is_amendment' => intval($row->gen_is_amendment),
                        'amendment_group_id' => $row->gen_amendment_group_id,
                        'reference_number' => $row->reference_number,
                        'object_code' => $row->object_code,
                        'description' => $row->description,
                        'extra_description' => $row->extra_description,
                        'comments' => $row->comments,
                        ]);

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
