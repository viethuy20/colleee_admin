<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffConfirmReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aff_confirm_reports', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->datetime('report_day')->comment('レポート日');
            $table->bigInteger('asp_id')->default(0)->comment('ASPID');
            $table->string('asp_name', 64)->default('')->comment('ASP名称');
            $table->bigInteger('affiriate_id')->default(0)->comment('アフィリエイトID');
            $table->bigInteger('program_id')->default(0)->comment('プログラムID');
            $table->string('program_title', 256)->default('')->comment('プログラム名');
            $table->integer('confirm_count')->nullable()->comment('確定件数');
            $table->integer('sum_point')->nullable()->comment('合計ポイント');
            $table->integer('point')->nullable()->comment('ポイント');
            $table->integer('bonus_point')->nullable()->comment('ボーナスポイント');
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
        Schema::dropIfExists('aff_confirm_reports');
    }
}
