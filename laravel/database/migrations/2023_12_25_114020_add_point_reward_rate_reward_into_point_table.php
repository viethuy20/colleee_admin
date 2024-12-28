<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPointRewardRateRewardIntoPointTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('points', function (Blueprint $table) {
            $table->decimal('reward_amount_rate',8,6)->nullable()->comment('報酬額報酬率')->after('rate');
            $table->integer('reward_amount')->nullable()->comment('報酬額ポイント')->after('rate');
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('points', function (Blueprint $table) {
            $table->dropColumn('reward_amount_rate');
            $table->dropColumn('reward_amount');
            //
        });
    }
}
