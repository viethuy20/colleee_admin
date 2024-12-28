<?php
$base_data = [
    'status' => [
        \App\FriendReferralBonusSchedule::STATUS_END     => '終了済み',
        \App\FriendReferralBonusSchedule::STATUS_START   => '公開中',
        \App\FriendReferralBonusSchedule::STATUS_STANDBY => '公開待ち'
    ],
];
return $base_data;