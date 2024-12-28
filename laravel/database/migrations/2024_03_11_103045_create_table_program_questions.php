<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableProgramQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('program_id')->comment('プログラムID');
            $table->text('question')->comment('質問');
            $table->text('answer')->comment('回答');
            $table->integer('disp_order')->comment('表示順');
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
        Schema::dropIfExists('program_questions');
    }
}
