<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FriendReferralBonusSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('friend_referral_bonus_schedule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name')->nullable()->comment('友達紹介報酬スケジュール名');
            $table->integer('reward_condition_point')->comment('獲得条件ポイント');
            $table->integer('friend_referral_bonus_point')->comment('友達紹介報酬ポイント');
            $table->datetime('start_at')->comment('紹介掲載開始日時');
            $table->datetime('stop_at')->comment('紹介掲載終了日時');
            $table->datetime('created_at')->comment('作成日時');
            $table->datetime('updated_at')->comment('更新日時');
            $table->datetime('deleted_at')->nullable()->comment('削除日時');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->index(['start_at', 'stop_at'], 'INDEX_start_at_stop_at');
        });

        DB::statement("ALTER TABLE `friend_referral_bonus_schedule` comment '友達紹介報酬スケジュール'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('friend_referral_bonus_schedule');
    }
}
