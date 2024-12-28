<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRequestCashbackKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_request_cashback_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('ユーザーID');
            $table->bigInteger('exchange_request_id')->comment('交換リクエストID');
            $table->text('cashback_id')->comment('cashbackId');
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
        Schema::dropIfExists('exchange_request_cashback_keys');
    }
}
