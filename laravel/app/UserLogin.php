<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * ログイン履歴.
 */
class UserLogin extends Model
{
    use DBTrait, PartitionTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'user_logins';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

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
        $partition_expired = 365;
        // 予約パーティション数
        $reserved_partition = 7;

        return self::refreshDateRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }

    public static function getLoginTotal($start_at, $end_at)
    {
        return UserLogin::whereBetween('created_at', [$start_at, $end_at])
            ->count('user_id');
    }

    public static function getUniqueLoginTotal($start_at, $end_at)
    {
        return UserLogin::whereBetween('created_at', [$start_at, $end_at])
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function getDayUserLogin($start_at, $end_at)
    {
         return DB::table('user_logins')
            ->select(DB::raw('Date(created_at) as date'), DB::raw('COUNT(created_at) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Date(created_at)'))->orderBy('date', 'asc')->get();
    }

    public static function getDayUniqueUserLogin($start_at, $end_at)
    {
         return DB::table('user_logins')
            ->select(DB::raw('Date(created_at) as date'), DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Date(created_at)'))->orderBy('date', 'asc')->get();
    }

    public static function getWeekUserLogin($start_at, $end_at)
    {
        return DB::table('user_logins')
            ->select(DB::raw('Week(created_at) as week'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Week(created_at)'))->orderBy('week', 'asc')->get();
    }

    public static function getWeekUniqueUserLogin($start_at, $end_at)
    {
        return DB::table('user_logins')
            ->select(DB::raw('Week(created_at) as week'), DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Week(created_at)'))->orderBy('week', 'asc')->get();
    }

    public static function getYearUserLogin($start_at, $end_at)
    {
        return DB::table('user_logins')
            ->select(DB::raw('Month(created_at) as month'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Month(created_at)'))->orderBy('month', 'asc')->get();
    }

    public static function getYearUniqueUserLogin($start_at, $end_at)
    {
        return DB::table('user_logins')
            ->select(DB::raw('Month(created_at) as month'), DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Month(created_at)'))->orderBy('month', 'asc')->get();
    }
}
