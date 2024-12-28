<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfferProgram extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'offer_programs';

    const PLATFORM_WEB = 1;
    const PLATFORM_ANDROID = 2;
    const PLATFORM_IOS = 3;

    const REVENUE_AMOUNT = 1;
    const REVENUE_RATE = 2;

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    public function offerCvPoints()
    {
        return $this->hasMany(OfferCVPoint::class, 'offer_program_id', 'id');
    }

    public function offerProgramCategories()
    {
        return $this->hasMany(OfferProgramCategories::class, 'offer_program_id', 'id');
    }
}
