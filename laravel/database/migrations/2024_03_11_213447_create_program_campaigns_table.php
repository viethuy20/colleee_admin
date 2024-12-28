<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('program_id')->comment('プログラムID');
            $table->text('title')->comment('タイトル');
            $table->text('campaign')->comment('キャンペーン');
            $table->text('url')->nullable()->comment('リンクURL');
            $table->dateTime('start_at')->comment('開始日時');
            $table->dateTime('stop_at')->comment('終了日時');
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
        Schema::dropIfExists('program_campaigns');
    }
}
