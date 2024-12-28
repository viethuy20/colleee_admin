<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypayLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paypay_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('ユーザーID');
            $table->bigInteger('exchange_request_id')->comment('交換リクエストID');
            $table->string('api_name')->comment('API名');
            $table->string('status_code')->comment('ステータス');
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
        Schema::dropIfExists('paypay_logs');
    }
}
