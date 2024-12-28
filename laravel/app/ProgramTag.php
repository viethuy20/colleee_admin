<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * プログラムタグ.
 */
class ProgramTag extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'program_tags';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
}
