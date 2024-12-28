<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramStockBatchLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_stock_batch_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('program_stock_id');
            $table->datetime('time_run_batch');
            $table->boolean('status_notify')->default(0)->comment("0: not run, 1: run |status_notifyはバッチ実行後アラートを表示するかどうかのステータスです。毎回バッチ実行後ステータスはアラートを表示することになります");

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->unique('program_stock_id');
            $table->index(['program_stock_id'], 'INDEX_program_stock_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_stock_batch_logs');
    }
}
