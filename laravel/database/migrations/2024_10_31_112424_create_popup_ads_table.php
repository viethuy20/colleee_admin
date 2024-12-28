<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePopupadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('popup_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('devices');
            $table->bigInteger('program_id');
            $table->text('title');
            $table->integer('priority');
            $table->dateTime('start_at');
            $table->dateTime('stop_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('popup_ads');
    }
}
