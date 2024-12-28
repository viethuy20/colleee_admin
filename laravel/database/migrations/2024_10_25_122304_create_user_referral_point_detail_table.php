<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserReferralPointDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_referral_point_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('target_month', 6); 
            $table->unsignedBigInteger('friend_user_id');
            $table->timestamp('friend_registered_at')->nullable(); 
            $table->integer('referral_bonus')->default(0)->nullable();
            $table->integer('friend_return_bonus')->default(0)->nullable();
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
        Schema::dropIfExists('user_referral_point_detail');
    }
}
