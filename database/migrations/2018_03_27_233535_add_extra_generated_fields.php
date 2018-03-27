<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtraGeneratedFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add gen_original_value and gen_is_most_recent_value (is final value)
        Schema::table('l_contracts', function (Blueprint $table) {
            $table->double('gen_original_value', 15, 2)->nullable();
            $table->boolean('gen_is_most_recent_value')->index()->default(0);
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
            $table->dropColumn('gen_original_value');
            $table->dropColumn('gen_is_most_recent_value');
        });
    }
}
