<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('ユーザーID');
            $table->bigInteger('type')->comment('交換種別ID');
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
        Schema::dropIfExists('api_logs');
    }
}
