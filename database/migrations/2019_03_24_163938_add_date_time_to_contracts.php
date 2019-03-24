<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateTimeToContracts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('l_contracts', function (Blueprint $table) {
            $table->dateTime('row_created_at')->nullable()->after('source_csv_filename');
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
            $table->dropColumn('row_created_at');
        });
    }
}
