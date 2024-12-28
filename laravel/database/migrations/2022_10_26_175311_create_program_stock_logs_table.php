<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_stock_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->datetime('start')->comment('バッチの開始時間');
            $table->datetime('end')->comment('終了時間');
            $table->string('result', 10)->comment('実行結果');
            $table->datetime('created_at')->comment('作成日時');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_stock_logs');
    }
}
