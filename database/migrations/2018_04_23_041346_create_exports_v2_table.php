<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExportsV2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exports_v2', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('vendor_clean', 255)->index();
            $table->string('owner_acronym', 12)->index()->comment('Department acronym.');

            // Value in the given year
            $table->double('yearly_value', 15, 2)->nullable();

            // We'll add a row for every year (inclusive) between the start and end effective years (in l_contracts and exports_v1)
            $table->integer('effective_year')->nullable()->index();


            // Source year (from metadata fields)
            $table->string('source_fiscal', 12)->nullable()->index();
            $table->integer('source_year')->nullable()->index();
            $table->integer('source_quarter')->nullable()->index();
            

            // Origin of the data (either scraped pages, or imported from CSV)
            $table->smallInteger('source_origin')->index()->comment('1 for scraper, 2 for CSV.');

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
        Schema::dropIfExists('exports_v2');
    }
}
