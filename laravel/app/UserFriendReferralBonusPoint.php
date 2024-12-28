<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/*
 * 友達紹介報酬スケジュール
 */
class UserFriendReferralBonusPoint extends Model
{

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'user_friend_referral_bonus_point';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

}
