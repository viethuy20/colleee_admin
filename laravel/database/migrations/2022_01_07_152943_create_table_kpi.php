<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableKpi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('number_of_logins')->nullable();
            $table->integer('number_of_logins_uu')->nullable();
            $table->integer('number_of_actions_uu')->nullable();
            $table->integer('action_rate')->nullable();
            $table->integer('number_of_new_members')->nullable();
            $table->integer('number_of_new_enrollment_actions')->nullable();
            $table->integer('number_of_new_action_rate')->nullable();
            $table->integer('number_of_unauthorized_withdrawals')->nullable();
            $table->integer('number_of_withdrawals')->nullable();
            $table->date('start_at');
            $table->date('end_at');
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
        Schema::dropIfExists('kpis');
    }
}
