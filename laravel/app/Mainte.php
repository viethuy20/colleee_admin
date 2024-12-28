<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * メンテナンス.
 */
class Mainte extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'maintes';

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at',];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
    ];

    
    public function getEditableAttribute() : bool
    {
        return isset($this->id) ? $this->start_at->gte(Carbon::now()) : true;
    }

    /**
     * メンテナンス初期値取得.
     * @return Mainte メンテナンス
     */
    public static function getDefault(int $type) : Mainte
    {
        $mainte = new self();
        $mainte->type = $type;
        $mainte->status = 0;
        
        $now = Carbon::now();
        $mainte->start_at = Carbon::create(
            $now->year,
            $now->month,
            $now->day,
            $now->hour,
            $now->minute,
            0
        );
        $mainte->stop_at = Carbon::parse('9999-12-31')->endOfMonth();
        return $mainte;
    }
}
