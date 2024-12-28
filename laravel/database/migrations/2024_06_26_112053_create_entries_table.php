<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('title');
            $table->dateTime('start_at');
            $table->dateTime('stop_at');
            $table->text('sub_text_pc')->nullable();  
            $table->text('main_text_pc')->nullable();  
            $table->text('sub_text_sp')->nullable();  
            $table->text('main_text_sp')->nullable();
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
        Schema::dropIfExists('entries');
    }
}
