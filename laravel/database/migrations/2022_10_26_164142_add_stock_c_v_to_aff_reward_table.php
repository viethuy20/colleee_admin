<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockCVToAffRewardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aff_rewards', function($table) {
            $table->tinyInteger('flag_stock')->default(0)->after('old');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aff_rewards', function($table) {
            $table->dropColumn('flag_stock');
        });
    }
}
