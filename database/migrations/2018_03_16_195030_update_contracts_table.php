<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add a gen_duplicate_source_id
        Schema::table('l_contracts', function (Blueprint $table) {
            $table->string('gen_duplicate_source_id', 255)->nullable()->after('gen_duplicate_via');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('l_contracts', function (Blueprint $table) {
            $table->dropColumn('gen_duplicate_source_id');
        });
    }
}
