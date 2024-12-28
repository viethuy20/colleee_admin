<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffActionReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aff_action_reports', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->datetime('report_day')->comment('レポート日');
            $table->bigInteger('asp_id')->default(0)->comment('ASPID');
            $table->string('asp_name', 64)->nullable()->comment('ASP名称');
            $table->bigInteger('affiriate_id')->default(0)->comment('アフィリエイトID');
            $table->bigInteger('program_id')->default(0)->comment('プログラムID');
            $table->string('program_title', 256)->nullable()->comment('プログラム名');
            $table->integer('click')->nullable()->comment('クリック');
            $table->integer('cv')->nullable()->comment('コンバージョン');
            $table->integer('sum_point')->nullable()->comment('合計ポイント');
            $table->integer('point')->nullable()->comment('ポイント');
            $table->integer('bonus_point')->nullable()->comment('商品価格');
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
        Schema::dropIfExists('aff_action_reports');
    }
}
