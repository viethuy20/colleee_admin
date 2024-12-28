<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

use App\FriendReferralBonusSchedule;

class FriendReferralBonusScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FriendReferralBonusSchedule::create([
            'name'                                 => 'お友達紹介ボーナス',
            'reward_condition_point'               => '3000',
            'friend_referral_bonus_point'          => '5000',
            'start_at'                             => '2022-07-01 00:00:00',
            'stop_at'                              => '2040-07-31 23:59:59',
        ]);
    }
}
