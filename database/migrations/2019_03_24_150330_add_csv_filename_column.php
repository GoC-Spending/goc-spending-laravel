<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCsvFilenameColumn extends Migration
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
            $table->string('source_csv_filename', 255)->nullable()->after('source_origin');
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
            $table->dropColumn('source_csv_filename');
        });
    }
}
