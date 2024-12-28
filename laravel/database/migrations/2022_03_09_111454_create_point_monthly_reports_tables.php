<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePointMonthlyReportsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_monthly_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->datetime('report_day')->comment('レポート日');
            $table->bigInteger('sum_balance_point')->default(0)->comment('ポイント残高');
            $table->bigInteger('sum_action_point')->default(0)->comment('新規発生ポイント付与高');
            $table->bigInteger('sum_confirm_point')->default(0)->comment('新規確定ポイント付与高');
            $table->bigInteger('sum_exchange_point')->default(0)->comment('既存ポイント引出高');
            $table->bigInteger('sum_lost_point')->default(0)->comment('失効ポイント高');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `point_monthly_reports` comment '月次ポイントレポート'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_monthly_reports');
    }
}
