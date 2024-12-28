<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recommend_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('device_type')->comment('デバイス種類');
            $table->bigInteger('program_id')->comment('プログラムID');
            $table->text('title')->comment('タイトル');
            $table->bigInteger('sort')->comment('並び順');
            $table->dateTime('start_at')->comment('開始日時');
            $table->dateTime('stop_at')->comment('終了日時');
            $table->dateTime('delete_at')->nullable(true)->comment('削除日時');
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
        Schema::dropIfExists('recommend_programs');
    }
}
