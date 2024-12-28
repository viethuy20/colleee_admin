<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class ProgramLabel extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'program_labels';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    /**
     * ラベル情報を保存.
     */
      /**
     * Add extra attribute.
     */
    protected $appends = [];
}
