<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * ユーザーポイント.
 */
class UserPoint extends Model
{
    use DBTrait;

    /** 組戻し. */
    const ROLLBACK_TYPE = 1;
    /** 広告. */
    const PROGRAM_TYPE = 2;
    /** モニター. */
    const MONITOR_TYPE = 3;
    /** アンケート. */
    const QUESTION_TYPE = 4;
    /** 口コミ. */
    const REVIEW_TYPE = 6;
    /** 管理者. */
    const ADMIN_TYPE = 7;
    /** 金融機関振込. */
    const BANK_TYPE = 8;
    /** 電子マネー交換. */
    const EMONEY_TYPE = 9;
    /** ギフトコード交換. */
    const GIFT_CODE_TYPE = 10;
    /** 他社ポイント交換. */
    const OTHER_POINT_TYPE = 11;
    /** 旧広告. */
    const OLD_PROGRAM_TYPE = 13;
    /** 特別広告. */
    const SP_PROGRAM_TYPE = 14;
    /** 成果あり特別広告. */
    const SP_PROGRAM_WITH_REWARD_TYPE = 15;
    /** ポイントボックス. */
    const POINTBOX_TYPE = 16;

    /** 誕生日ボーナス. */
    const BIRTYDAY_BONUS_TYPE = 20;
    /** 広告ボーナス. */
    const PROGRAM_BONUS_TYPE = 21;
    /** お友達紹介ボーナス. */
    const ENTRY_BONUS_TYPE = 22;
    /** ゲーム. */
    const GAME_BONUS_TYPE = 23;

    /** ポイント失効タイトル. */
    const EXPIRE_TITLE = 'ポイント失効';

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'user_points';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 初期値を取得.
     * @param int $user_id ユーザーID
     * @param int $type 種類
     * @param int $diff_point 差分ポイント
     * @param int $bonus_point ボーナスポイント
     * @param string $title タイトル
     * @return \App\UserPoint
     */
    public static function getDefault(int $user_id, int $type, int $diff_point, int $bonus_point, string $title) :
        UserPoint
    {
        $user_point = new UserPoint();
        $user_point->user_id = $user_id;
        $user_point->type = $type;
        $user_point->diff_point = $diff_point;
        $user_point->bonus_point = $bonus_point;
        $user_point->title = $title;
        return $user_point;
    }

    /**
     * 最後のポイント情報を登録.
     */
    private function setLastPoint()
    {
        // 最後のポイント更新を取得
        $last_point = self::where('user_id', '=', $this->user_id)
            ->orderBy('id', 'desc')
            ->first();

        if (isset($last_point->id)) {
            $this->point = $last_point->point;
            $this->exchanged_point = $last_point->exchanged_point;
        } else {
            $this->point = 0;
            $this->exchanged_point = 0;
        }
        $this->point = $this->point + $this->diff_point + $this->bonus_point;
    }

    /**
     * ポイントロック.
     * @param type $save_func 保存関数
     * @param type $check_func 検証関数
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function lockPoint($save_func = null, $check_func = null) : bool
    {
        return self::saveWithLock(
            sprintf("user_point_%d", $this->user_id),
            function () use ($save_func) {
                return $save_func();
            },
            $check_func
        );
    }

    /**
     * ポイント更新.
     * @param type $check_func 検証関数
     * @param type $next_check_func 検証関数2
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function addPoint($check_func = null, $next_check_func = null) : bool
    {
        $user_point = $this;
        return self::lockPoint(
            function () use ($user_point, $next_check_func) {
                // 関数が存在した場合は実行して、関数の戻り値がfalseの場合は正常終了する
                if (isset($next_check_func) && !($next_check_func())) {
                    return true;
                }

                $user_point->setLastPoint();
                if ($user_point->point < 0) {
                    return false;
                }
                // 保存実行
                $user_point->save();

                // 調整用ポイントの場合
                if ($user_point->diff_point == 0 && $user_point->bonus_point == 0) {
                    return true;
                }

                // ユーザー更新日時更新
                $now = Carbon::now();
                $today = $now->copy()->startOfDay();

                User::where('id', '=', $user_point->user_id)
                    ->where(function ($query) use ($today) {
                        $query->orWhereNull('actioned_at')
                            ->orWhere('actioned_at', '<', $today);
                    })
                    ->update(['actioned_at' => $now]);
                return true;
            },
            $check_func
        );
    }

    /**
     * ポイント組戻し.
     * @param int $admin_id 更新管理者ID
     * @param string $title タイトル
     * @param type $check_func 検証関数
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function rollbackPoint(int $admin_id, string $title, $check_func = null) : bool
    {
        // 交換処理ではない場合、組戻しをさせない
        if ($this->diff_point >= 0) {
            return false;
        }
        $user_point = $this;
        return self::lockPoint(
            function () use ($admin_id, $user_point, $title) {
                // 既に組戻されているか確認して、組戻しが発生していた場合は終了
                $exist = UserPoint::where('type', '=', $this::ROLLBACK_TYPE)
                        ->where('parent_id', '=', $user_point->id)
                        ->exists();
                if ($exist) {
                    return false;
                }

                // 組戻しを実行
                $next_user_point = UserPoint::getDefault(
                    $user_point->user_id,
                    UserPoint::ROLLBACK_TYPE,
                    -$this->diff_point,
                    0,
                    $title
                );
                $next_user_point->parent_id = $user_point->id;
                $next_user_point->admin_id = $admin_id;
                $next_user_point->setLastPoint();
                $next_user_point->exchanged_point = $next_user_point->exchanged_point - $next_user_point->diff_point;
                $next_user_point->save();
                return true;
            },
            $check_func
        );
    }

    /**
     * ユーザーポイントマップ取得.
     * @param array $user_id_list ユーザーIDリスト
     * @param Carbon $date 基準日時
     * @return array ユーザーポイントマップ
     */
    public static function getUserPointMap(array $user_id_list, Carbon $date = null) : array
    {
        // ユーザーIDが空の場合
        if (empty($user_id_list)) {
            return [];
        }
        $target_date = $date ?? Carbon::now();

        return self::whereIn(
            DB::raw('(user_id, id)'),
            function ($query) use ($user_id_list, $target_date) {
                $query->select(DB::raw('user_id, max(id)'))
                    ->from('user_points')
                    ->whereIn('user_id', $user_id_list)
                    ->where('created_at', '<', $target_date)
                    ->groupBy('user_id');
            }
        )
            ->get()
            ->keyBy('user_id')
            ->all();
    }

    /**
     * 獲得ポイントマップ取得.
     * @param array $user_id_list ユーザーIDリスト
     * @param Carbon $date 基準日時
     * @return array 獲得ポイントマップ
     */
    public static function getUpPointMap(array $user_id_list, Carbon $date) : array
    {
        // ユーザーIDが空の場合
        if (empty($user_id_list)) {
            return [];
        }
        return self::selectRaw('user_id, sum(diff_point + bonus_point) as tp')
            ->where('type', '<>', self::ROLLBACK_TYPE)
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->where('diff_point', '>=', 0)
            ->where('bonus_point', '>=', 0)
            ->groupBy('user_id')
            ->pluck('tp', 'user_id')
            ->all();
    }

    /**
     * ポイント失効.
     * @param int $admin_id 管理者ID
     * @param array $user_id_list ユーザーIDリスト
     */
    public static function expirePoint(int $admin_id, array $user_id_list)
    {
        $user_point_map = self::getUserPointMap($user_id_list);
        foreach ($user_point_map as $user_id => $user_point) {
            // ポイントが0以下の場合は何もしない
            if ($user_point->point <= 0) {
                continue;
            }

            // ポイント失効を実行
            $next_user_point = self::getDefault($user_id, self::ADMIN_TYPE, 0, 0, self::EXPIRE_TITLE);
            $next_user_point->admin_id = $admin_id;
            $next_user_point->lockPoint(
                function () use ($next_user_point) {
                    // 現在のポイントを取得
                    $user_point = UserPoint::where('user_id', '=', $next_user_point->user_id)
                        ->orderBy('id', 'desc')
                        ->first();
                    // ポイントが0以下の場合は何もしない
                    if ($user_point->point <= 0) {
                        return false;
                    }

                    // ポイント失効を実行
                    $next_user_point->diff_point = -$user_point->point;
                    $next_user_point->setLastPoint();
                    $next_user_point->save();
                    return true;
                }
            );
            //\Log::info('UserPoint expire[user_id:'.$user_id.',point:'.$point.']');
        }
    }
}
