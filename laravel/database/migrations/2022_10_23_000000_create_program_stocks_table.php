<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProgramStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('program_id')->comment('プログラムID');
            $table->integer('stock_cv')->nullable()->comment('在庫CV数');
            $table->datetime('created_at')->comment('作成日時');
            $table->datetime('updated_at')->comment('更新日時');
            $table->datetime('deleted_at')->nullable()->comment('削除日');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->unique('program_id');
            $table->index(['program_id'], 'INDEX_program_id');
            $table->index(['created_at'], 'INDEX_created_at');
        });

        DB::statement("ALTER TABLE `program_stocks` comment 'ユーザー友達紹介報酬情報'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_stocks');
    }
}
