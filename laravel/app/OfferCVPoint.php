<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfferCVPoint extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'offer_cv_points';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    public function offerProgram()
    {
        return $this->belongsTo(OfferProgram::class, 'offer_program_id', 'id');
    }
}
