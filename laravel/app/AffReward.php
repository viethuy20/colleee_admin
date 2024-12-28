<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * アフィリエイト成果.
 */
class AffReward extends Model
{
    use DBTrait, PartitionTrait;

    /** 正常. */
    const SUCCESS_CODE = 0;
    /** 書式. */
    const FORMAT_ERROR_CODE = 1;
    /** ユーザーが存在しない. */
    const USER_NOT_EXIST_CODE = 2;
    /** アフィリエイトが存在しない. */
    const AFFIRIATE_NOT_EXIST_CODE = 3;
    /** ポイントが0. */
    const POINT_ZERO_CODE = 4;
    /** 重複状態. */
    const CONFLICT_CODE = 5;
    /** テストプログラム. */
    const TEST_PROGRAM_CODE = 6;
    /** クリックが存在しない. */
    const CLICK_NOT_EXIST_CODE = 7;
    /** コースが存在しない. */
    const COURSE_NOT_EXIST_CODE = 8;

    /** 配布済み状態. */
    const REWARDED_STATUS = 0;
    /** キャンセル状態. */
    const CANCELED_STATUS = 1;
    /** 配布待ち状態. */
    const WAITING_STATUS = 2;
    /** 異常状態. */
    const ERROR_STATUS = 3;
    /** 発生状態. */
    const ACTIONED_STATUS = 4;
    /** 自動キャンセル状態. */
    const AUTO_CANCELED_STATUS = 5;

    /** flag stock */
    const FLAG_STOCK_ON = 1;
    const FLAG_STOCK_OFF = 0;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'aff_rewards';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'actioned_at', 'status_updated_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'actioned_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];
    /**
     * 更新日時更新停止.
     * @var bool
     */
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function affiriate()
    {
        return $this->belongsTo(Affiriate::class, 'affiriate_id', 'id');
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

    public static function updateFlagStock($id)
    {
        static::where('id', '=', $id)
            ->update([
                'flag_stock' => self::FLAG_STOCK_ON,
                'updated_at' => Carbon::now(),
            ]);
        return true;
    }

    public static function getUniqueActionTotal($start_at, $end_at)
    {
        return AffReward::orWhere(function ($query) use ($start_at, $end_at) {
            $query->where(function ($query) use ($start_at, $end_at) {
                $query->whereNull('actioned_at');
                $query->whereBetween('created_at', [$start_at, $end_at]);
            });
            $query->orWhere(function ($query) use ($start_at, $end_at) {
                $query->where(function ($query) use ($start_at, $end_at) {
                    $query->whereNotNull('actioned_at');
                    $query->whereBetween('actioned_at', [$start_at, $end_at]);
                });
            });
        })
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function getCreatedActionTotal($start_at, $end_at)
    {
      return   AffReward::whereIn('user_id', function ($query) use ($start_at, $end_at) {
            $query->select('id')
                ->from('users')
                ->whereBetween('created_at', [$start_at, $end_at]);
        })
            ->where(function ($query) use ($start_at, $end_at) {
                $query->where(function ($query) use ($start_at, $end_at) {
                    $query->whereNull('actioned_at');
                    $query->whereBetween('created_at', [$start_at, $end_at]);
                });
                $query->orWhere(function ($query) use ($start_at, $end_at) {
                    $query->where(function ($query) use ($start_at, $end_at) {
                        $query->whereNotNull('actioned_at');
                        $query->whereBetween('actioned_at', [$start_at, $end_at]);
                    });
                });
            })
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function getDayUniqueActionTotal($start_at, $end_at)
    {
        return AffReward::select(DB::raw('Date(COALESCE(actioned_at, created_at)) as date'), DB::raw('COALESCE(actioned_at, created_at) as dateTime'),
            DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->havingBetween('dateTime', [$start_at, $end_at])
            ->groupBy('date')->orderBy('date', 'asc')->get();
    }

    public static function getWeekUniqueActionTotal($start_at, $end_at)
    {
        return AffReward::select(DB::raw('Week(COALESCE(actioned_at, created_at)) as week'),
            DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->whereBetween(DB::raw('COALESCE(actioned_at, created_at)'), [$start_at, $end_at])
            ->groupBy('week')->orderBy('week', 'asc')->get();
    }

    public static function getYearUniqueActionTotal($start_at, $end_at)
    {
        return AffReward::select(DB::raw('Month(COALESCE(actioned_at, created_at)) as month'), DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->whereBetween(DB::raw('COALESCE(actioned_at, created_at)'), [$start_at, $end_at])
            ->groupBy('month')->orderBy('month', 'asc')->get();
    }

    public static function getDayCreatedActionTotal($start_at, $end_at)
    {

        return AffReward::select(DB::raw('Date(COALESCE(aff_rewards.actioned_at, aff_rewards.created_at)) as date'),
            DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->join('users', function ($query) use ($start_at, $end_at) {
                $query->on('aff_rewards.user_id', 'users.id')
                    ->whereBetween('users.created_at', [$start_at, $end_at])->where(DB::raw('Date(users.created_at)'), DB::raw('Date(COALESCE(aff_rewards.actioned_at, aff_rewards.created_at))'));
            })
            ->whereBetween(DB::raw('COALESCE(aff_rewards.actioned_at, aff_rewards.created_at)'), [$start_at, $end_at])
            ->groupBy('date')->orderBy('date', 'asc')->get();
    }

    public static function getWeekCreatedActionTotal($start_at, $end_at)
    {
        return AffReward::select(DB::raw('Week(COALESCE(aff_rewards.actioned_at, aff_rewards.created_at)) as week'), DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->join('users', function ($query) use ($start_at, $end_at) {
                $query->on('aff_rewards.user_id', 'users.id')
                    ->whereBetween('users.created_at', [$start_at, $end_at])->where(DB::raw('Week(users.created_at)'), DB::raw('Week(COALESCE(aff_rewards.actioned_at, aff_rewards.created_at))'));
            })
            ->whereBetween(DB::raw('COALESCE(aff_rewards.actioned_at, aff_rewards.created_at)'), [$start_at, $end_at])
            ->groupBy('week')->orderBy('week', 'asc')->get();
    }

    public static function getMonthCreatedActionTotal($start_at, $end_at)
    {
          return AffReward::select(DB::raw('Month(COALESCE(aff_rewards.actioned_at, aff_rewards.created_at)) as month'), DB::raw('COUNT(DISTINCT user_id) as number_of_users'))
            ->join('users', function ($query) use ($start_at, $end_at) {
                $query->on('aff_rewards.user_id', 'users.id')
                    ->whereBetween('users.created_at', [$start_at, $end_at])->where(DB::raw('month(users.created_at)'), DB::raw('month(COALESCE(aff_rewards.actioned_at, aff_rewards.created_at))'));
            })
            ->whereBetween(DB::raw('COALESCE(aff_rewards.actioned_at, aff_rewards.created_at)'), [$start_at, $end_at])
            ->groupBy('month')->orderBy('month', 'asc')->get();

    }
}
