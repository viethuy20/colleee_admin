<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UserFriendReferralBonusPoint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_friend_referral_bonus_point', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('招待されたユーザーID');
            $table->bigInteger('friend_user_id')->comment('招待したユーザーID');
            $table->integer('friend_referral_bonus_schedule_id')->comment('友達紹介報酬スケジュールID');
            $table->text('name')->nullable()->comment('友達紹介報酬スケジュール名');
            $table->integer('reward_condition_point')->comment('獲得条件ポイント');
            $table->integer('friend_referral_bonus_point')->comment('友達紹介報酬ポイント');
            $table->datetime('created_at')->comment('作成日時');
            $table->datetime('updated_at')->comment('更新日時');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->unique('user_id');
            $table->index(['user_id'], 'INDEX_user_id');
            $table->index(['friend_user_id'], 'INDEX_friend_user_id');
            $table->index(['created_at'], 'INDEX_created_at');
        });

        DB::statement("ALTER TABLE `user_friend_referral_bonus_point` comment 'ユーザー友達紹介報酬情報'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_friend_referral_bonus_point');
    }
}
