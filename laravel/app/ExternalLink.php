<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * クリック履歴.
 */
class ExternalLink extends Model
{
    use DBTrait, PartitionTrait;
    
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'external_links';
    
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }
    public function asp()
    {
        return $this->belongsTo(Asp::class, 'asp_id', 'id');
    }
    
    /**
     * ユーザー名を取得.
     * @return string ユーザー名
     */
    public function getUserNameAttribute()
    {
        return User::getNameById($this->user_id);
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
        $partition_expired = 180;
        // 予約パーティション数
        $reserved_partition = 7;
        
        return self::refreshDateRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }
}
