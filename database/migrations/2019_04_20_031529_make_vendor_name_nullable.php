<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeVendorNameNullable extends Migration
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
            $table->string('vendor_name', 255)->nullable()->change();
            $table->string('gen_vendor_clean', 255)->nullable()->change();
            $table->string('gen_contract_id', 255)->nullable()->change();
            $table->string('json_id', 255)->nullable()->change();
            $table->string('reference_number', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Keep them null to avoid rollback errors
        Schema::table('l_contracts', function (Blueprint $table) {
            // $table->string('vendor_name', 255)->nullable(false)->change();
            // $table->string('gen_vendor_clean', 255)->nullable(false)->change();
            // $table->string('gen_contract_id', 255)->nullable(false)->change();
            // $table->string('json_id', 255)->nullable(false)->change();
            // $table->string('reference_number', 255)->nullable(false)->change();
        });
    }
}
