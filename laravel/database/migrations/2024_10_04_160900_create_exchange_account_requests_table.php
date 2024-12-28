<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeAccountRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_account_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('ユーザーID');
            $table->bigInteger('type')->comment('交換種別ID');
            $table->string('session_key')->comment('sessionキー');
            $table->text('request');
            $table->text('response');
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
        Schema::dropIfExists('exchange_account_requests');
    }
}
