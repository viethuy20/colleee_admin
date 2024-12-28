<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('order_id', 256)->nullable()->comment('案件ID');
            $table->string('title', 256)->comment('アンケートタイトル');
            $table->unsignedBigInteger('media_id')->comment('媒体ID');
            $table->bigInteger('point')->default(0)->comment('ポイント数');
            $table->timestamp('answered_at')->nullable()->comment('アンケート回答日');
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
        Schema::dropIfExists('survey_histories');
    }
}
