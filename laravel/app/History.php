<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * 履歴.
 */
class History extends Model
{
    use DBTrait, PartitionTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'histories';

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 更新日時更新停止.
     * @var bool
     */
    public $timestamps = false;

    const DOT_MONEY_TYPE = 1;

    /**
     * 最適化実行.
     */
    public static function refreshPartition()
    {
        $instance = new static;

        $db_name = config('database.connections.mysql.database');
        //
        $tb_name = $instance->table;
        // パーティション有効期限
        $partition_expired = 36;
        // 予約パーティション数
        $reserved_partition = 4;

        return self::refreshMonthRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }

    public function setDataAttribute($data)
    {
        $this->attributes['data'] = isset($data) ? json_encode($data) : null;
    }

    public function getDataAttribute()
    {
        return isset($this->attributes['data']) ?  json_decode($this->attributes['data']) : null;
    }

    /**
     * 履歴保存.
     * @param int $type 種類
     * @param mixid $data
     */
    public static function addHistory(int $type, $data)
    {
        $history = new self();
        $history->created_at = Carbon::now();
        $history->type = $type;
        $history->data = $data;

        // 保存実行
        $history->save();
    }
}
