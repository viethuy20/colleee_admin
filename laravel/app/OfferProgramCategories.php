<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfferProgramCategories extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'offer_program_categories';

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
 
    
    public function offerProgram()
    {
        return $this->belongsTo(OfferProgram::class);
    }
}
