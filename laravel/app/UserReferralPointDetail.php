<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/*
 * 友達紹介報酬スケジュール
 */
class UserReferralPointDetail extends Model
{

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'user_referral_point_detail';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

}
