<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewPointManagementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_point_managements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('point')->comment('獲得ポイント');
            $table->datetime('start_at')->nullable()->comment('ポイント適用開始日時');
            $table->datetime('stop_at')->nullable()->comment('ポイント適用終了日時');
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
        Schema::dropIfExists('review_point_managements');
    }
}
