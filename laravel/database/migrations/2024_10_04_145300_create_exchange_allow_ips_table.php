<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeAllowIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_allow_ips', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('type')->comment('ポイント交換種別ID');
            $table->text('allow_ips')->comment('許可IPアドレス');
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
        Schema::dropIfExists('exchange_allow_ips');
    }
}
