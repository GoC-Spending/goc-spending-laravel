<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // gen_vendor_clean
        // owner_acronym
        // gen_effective_total_value
        // gen_effective_yearly_value
        // gen_original_value
        // gen_effective_start_year
        // gen_effective_end_year
        // source_fiscal
        // source_year
        // source_quarter
        // gen_is_most_recent_value
        // gen_is_amendment
        // gen_amendment_group_id
        // reference_number
        // object_code
        // description
        // extra_description
        // comments


        // where source_fiscal is not null
        // and where gen_is_duplicate = 0

        Schema::create('exports_v1', function (Blueprint $table) {
            $table->increments('id');

            $table->string('vendor_clean', 255)->index();
            $table->string('owner_acronym', 12)->index()->comment('Department acronym.');

            $table->double('effective_total_value', 15, 2)->nullable();
            $table->double('effective_yearly_value', 15, 2)->nullable();


            $table->double('original_value', 15, 2)->nullable();

            $table->integer('effective_start_year')->nullable()->index();
            $table->integer('effective_end_year')->nullable()->index();


            // Source year (from metadata fields)
            $table->string('source_fiscal', 12)->nullable()->index();
            $table->integer('source_year')->nullable()->index();
            $table->integer('source_quarter')->nullable()->index();
            

            // Origin of the data (either scraped pages, or imported from CSV)
            $table->smallInteger('source_origin')->index()->comment('1 for scraper, 2 for CSV.');

            $table->smallInteger('is_most_recent_value')->index()->default(0);

            $table->smallInteger('is_amendment')->index()->default(0);
            $table->string('amendment_group_id', 255)->nullable()->index();

            $table->string('reference_number', 255)->index();
            $table->string('object_code', 12)->nullable()->index();
            $table->text('description')->nullable();
            $table->text('extra_description')->nullable();
            $table->text('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exports_v1');
    }
}
