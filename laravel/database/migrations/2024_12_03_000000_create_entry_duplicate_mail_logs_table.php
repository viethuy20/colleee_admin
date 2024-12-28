<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntryDuplicateMailLogsTable extends Migration
{
    /**
     * Run the migrations.
     * 新規会員の仮登録時メールアドレスが既存アカウントと重複している場合のログテーブル
     * @return void
     */
    public function up()
    {
        Schema::create('entry_duplicate_mail_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('ユーザーID');
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
        Schema::dropIfExists('entry_duplicate_mail_logs');
    }
}
