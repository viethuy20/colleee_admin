<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExchangeAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_accounts', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->integer('type')->comment('交換種類');
            $table->bigInteger('user_id')->comment('ユーザーID');
            $table->string('number', 256)->nullable()->comment('会員番号');
            $table->text('data')->nullable()->comment('データ');

            $table->datetime('created_at')->comment('作成日時');
            $table->datetime('updated_at')->comment('更新日時');
            $table->datetime('deleted_at')->nullable()->comment('削除日時');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->unique(['type', 'user_id']);
            $table->index(['type'], 'INDEX_type');
            $table->index(['user_id'], 'INDEX_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_accounts');
    }
}
