<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Values from JSON
        // "uuid",
        // "vendor_name",
        // "reference_number",
        // "contract_date",
        // "description",
        // "extra_description",
        // "object_code",
        // "contract_period_start",
        // "contract_period_end",
        // "start_year",
        // "end_year",
        // "delivery_date",
        // "original_value",
        // "contract_value",
        // "comments",
        // "owner_acronym",
        // "source_year",
        // "source_quarter",
        // "source_fiscal",
        // "source_filename",
        // "source_url",
        // "vendor_clean",

        Schema::create('contracts', function (Blueprint $table) {
            $table->increments('id');

            // Original values from JSON:
            $table->string('json_id', 255)->index(); // From "uuid" values
            $table->string('vendor_name', 255)->index();
            $table->string('owner_acronym', 12)->index()->comment('Department acronym.');
            
            $table->double('contract_value', 15, 2);
            $table->double('original_value', 15, 2)->nullable();



            $table->string('reference_number', 255)->index();

            // "Dates" with original formatting from scraped pages:
            $table->string('raw_contract_date', 255)->nullable();
            $table->string('raw_delivery_date', 255)->nullable();
            $table->string('raw_contract_period_start', 255)->nullable();
            $table->string('raw_contract_period_end', 255)->nullable();

            // Description fields:
            $table->string('object_code', 12)->nullable()->index();
            $table->text('description')->nullable();
            $table->text('extra_description')->nullable();
            $table->text('comments')->nullable();


            
            // Source year (from metadata fields)
            $table->integer('source_year')->nullable()->index();
            $table->integer('source_quarter')->nullable()->index();
            $table->string('source_fiscal', 12)->nullable()->index();

            // Origin of the data (either scraped pages, or imported from CSV)
            $table->integer('source_origin')->index()->comment('1 for scraper, 2 for CSV.');


            // Generated / calculated values in PHP
            // Used for duplicate and amendment detection
            // and experimental calculations.
            $table->integer('gen_start_year')->nullable()->index();
            $table->integer('gen_end_year')->nullable()->index();

            $table->string('gen_vendor_clean', 255)->index();
            $table->string('gen_contract_id', 255)->index();
            
            $table->boolean('gen_is_duplicate')->index()->default(0);
            $table->integer('gen_duplicate_via')->nullable()->index();

            $table->boolean('gen_is_amendment')->index()->default(0);
            $table->integer('gen_amendment_via')->nullable()->index();
            $table->string('gen_amendment_group_id', 255)->nullable()->index();

            $table->integer('gen_effective_start_year')->nullable()->index();
            $table->integer('gen_effective_end_year')->nullable()->index();
            $table->double('gen_effective_total_value', 15, 2)->nullable();
            $table->double('gen_effective_yearly_value', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
