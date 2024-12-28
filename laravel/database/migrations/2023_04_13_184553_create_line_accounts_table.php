<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('line_id')->nullable()->comment('LINEID');
            $table->text('token')->nullable()->comment('LINEアクセストークン');
            $table->string('referenceNo')->nullable()->comment('LINE Payナンバー（11文字）'); // 基本11文字の文字列ではいってくる
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
        Schema::dropIfExists('line_accounts');
    }
}
