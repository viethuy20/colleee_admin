<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 特別プログラム種類.
 */
class SpProgramType extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'sp_program_types';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    public function getAspAttribute()
    {
        return $this->belongsTo(Asp::class, 'asp_id', 'id');
    }
}
