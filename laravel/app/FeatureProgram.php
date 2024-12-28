<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * 特集広告.
 */
class FeatureProgram extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'feature_programs';
    
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at', 'deleted_at'];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    /**
     * 特集広告情報初期値取得.
     * @return FeatureProgram 特集広告
     */
    public static function getDefault() : FeatureProgram
    {
        return new FeatureProgram();
    }
}
