<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddErrorColumns extends Migration
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
            $table->boolean('gen_is_error')->index()->default(0)->after('gen_contract_id');
            $table->integer('gen_error_via')->nullable()->index()->after('gen_contract_id');
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
            $table->dropColumn('gen_is_error');
            $table->dropColumn('gen_error_via');
        });
    }
}
