<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeAccountUserKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_account_user_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('type')->comment('ポイント交換種別ID');
            $table->bigInteger('user_id')->comment('ユーザーID');
            $table->string('key')->comment('キー');
            $table->string('referrer_code')->comment('表示名')->nullable();
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
        Schema::dropIfExists('exchange_account_user_keys');
    }
}
