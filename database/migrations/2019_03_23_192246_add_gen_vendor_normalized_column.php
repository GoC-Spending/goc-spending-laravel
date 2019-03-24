<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGenVendorNormalizedColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        // Add a gen_vendor_normalized to each table
        Schema::table('l_contracts', function (Blueprint $table) {
            $table->string('gen_vendor_normalized', 255)->nullable()->index()->after('gen_vendor_clean');
        });

        Schema::table('exports_v1', function (Blueprint $table) {
            $table->string('vendor_normalized', 255)->nullable()->index()->after('vendor_clean');
        });

        Schema::table('exports_v2', function (Blueprint $table) {
            $table->string('vendor_normalized', 255)->nullable()->index()->after('vendor_clean');
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
            $table->dropColumn('gen_vendor_normalized');
        });

        Schema::table('exports_v1', function (Blueprint $table) {
            $table->dropColumn('vendor_normalized');
        });

        Schema::table('exports_v2', function (Blueprint $table) {
            $table->dropColumn('vendor_normalized');
        });
    }
}
