<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 電話トークン.
 */
class OstToken extends Model
{
    use DBTrait, PartitionTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'ost_tokens';
    /**
     * 文字列キー有効化.
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * createメソッド実行時に、入力を許可するカラムの指定
     * @var array
     */
    protected $fillable = ['id', 'expired_at', 'status', 'tel'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['expired_at'];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

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
        $partition_expired = 31;
        // 予約パーティション数
        $reserved_partition = 7;

        return self::refreshDateRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }
}
