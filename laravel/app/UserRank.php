<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * ユーザーランク.
 */
class UserRank extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'user_ranks';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    const RANK_NORMAL = 0;
    const RANK_SILVER = 2;
    const RANK_GOLD = 3;

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at'];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
    ];
    
    public function scopeOfTerm($query, Carbon $d)
    {
        return $query->where('stop_at', '>=', $d)
            ->where('start_at', '<=', $d);
    }
    
    public static function updateRank(Carbon $stop_at, array $stop_user_rank_id_list, array $insert_user_rank_list)
    {
        DB::transaction(function () use ($stop_at, $stop_user_rank_id_list, $insert_user_rank_list) {
            // 停止
            UserRank::whereIn('id', $stop_user_rank_id_list)
                ->update(['stop_at' => $stop_at]);
            // 新規ランクを一括登録
            UserRank::insert($insert_user_rank_list);
            return true;
        });
    }
    
    /**
     * ユーザーランクマップ取得.
     * @param array $user_id_list ユーザーIDリスト
     * @return array ユーザーランクマップ
     */
    public static function getUserRankMap(array $user_id_list) : array
    {
        // ユーザーIDが空の場合
        if (empty($user_id_list)) {
            return [];
        }
        
        return self::select('user_id', 'rank')
            ->whereIn('user_id', $user_id_list)
            ->ofTerm(Carbon::now())
            ->pluck('rank', 'user_id')
            ->all();
    }
}
