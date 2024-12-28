<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class PreAffReward extends Model
{
    use DBTrait, PartitionTrait;

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /** 1成果あたりの先出上限ポイント数. */
    const MAX_PRE_REWARD_POINT = 3000000;

    /** ブラックリスト入りする損害上限ポイント数. */
    const BLOCK_POINT = 100000;

    /** 正常配付状態. */
    const SUCCESS_STATUS = 0;
    /** 損失配付状態. */
    const DAMAGE_STATUS = 1;
    /** 先行配付状態. */
    const REWARDED_STATUS = 2;
    /** 除外状態. */
    const EXCLUDED_STATUS = 3;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'pre_aff_rewards';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    // @codingStandardsIgnoreStart
    public function aff_reward()
    {
        // @codingStandardsIgnoreEnd
        return $this->belongsTo(AffReward::class, 'aff_reward_id', 'id');
    }

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
        $partition_expired = 400;
        // 予約パーティション数
        $reserved_partition = 7;

        return self::refreshDateRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }
}
