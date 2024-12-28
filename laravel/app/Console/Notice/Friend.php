<?php
namespace App\Console\Notice;

use Carbon\Carbon;
use DB;

use App\AffReward;
use App\Asp;
use App\Console\BaseCommand;
use App\User;
use App\UserEditLog;
use App\UserLogin;
use App\UserPoint;

/**
 * Description of Friend
 *
 * @author t_moriizumi
 */
class Friend extends BaseCommand
{
    private static $EXCEPT_ASP_ID_LIST = [19, 20, 23];

    protected $tag = 'notice:friend';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notice:friend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notice friend';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function getBonusPoint(int $user_id, int $point, Carbon $start, Carbon $end)
    {
        // 前月の友達ユーザー登録数取得
        $friend_total = User::where('friend_user_id', '=', $user_id)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
            ->count();

        // 倍率計算
        $rate = 0.05;
        if ($friend_total > 50) {
            $rate = 0.1;
        } elseif ($friend_total > 40) {
            $rate = 0.09;
        } elseif ($friend_total > 30) {
            $rate = 0.08;
        } elseif ($friend_total > 20) {
            $rate = 0.07;
        } elseif ($friend_total > 10) {
            $rate = 0.06;
        }

        return ceil($point * $rate);
    }

    private function getFriendProgramAmount(Carbon $start_month)
    {
        $entry_start = $start_month->copy()->startOfMonth()->addMonths(-11);
        $end = $start_month->copy()->endOfMonth();

        $total = 0;

        $last_user_id = 1;
        while (true) {
            // ユーザーIDを取得
            $user_id = User::where('friend_user_id', '>', $last_user_id)
                ->whereBetween('created_at', [$entry_start, $end])
                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->orderBy('friend_user_id', 'asc')
                ->pluck('friend_user_id')
                ->first();

            // 終了
            if (empty($user_id)) {
                break;
            }
            $last_user_id = $user_id;

            // 友達ユーザーIDリストを取得
            $user_id_list = User::where('friend_user_id', '=', $user_id)
                ->whereBetween('created_at', [$entry_start, $end])
                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->pluck('id')
                ->all();

            // ポイント数取得
            $diff_point = UserPoint::whereIn('user_points.user_id', $user_id_list)
                ->whereBetween('user_points.created_at', [$start_month, $end])
                ->where('user_points.type', '=', UserPoint::PROGRAM_TYPE)
                ->join('points', function ($join) {
                    $join->on('user_points.parent_id', '=', 'points.program_id')
                        ->whereRaw('user_points.created_at BETWEEN points.start_at AND points.stop_at');
                })
                ->sum('user_points.diff_point');

            // 友達がボーナス対象広告を実行していない場合、ポイント配布しない
            if (empty($diff_point)) {
                continue;
            }

            $total = $total + $this->getBonusPoint($user_id, $diff_point, $start_month, $end);
        }

        return floor($total / 10);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // タグ作成
        $this->info('start');

        $end = Carbon::yesterday()
            ->endOfDay();

        // 対象日時
        $start_year = Carbon::now()->startOfYear();
        $start_month = Carbon::now()->startOfMonth();
        $start_day = $end->copy()->startOfDay();

        // 対象端末
        $device_map = [0 => 'PC', 1 => 'SP'];


        $asp_name_list = Asp::whereIn('id', self::$EXCEPT_ASP_ID_LIST)
            ->pluck('name')
            ->all();

        $except = empty($asp_name_list) ? null : implode(',', $asp_name_list);

        // 月間端末別子ユーザー紹介数
        $sp_month_total_map = User::select(DB::raw('sp, count(*) as total'))
            ->where('friend_user_id', '>', 0)
            ->whereBetween('created_at', [$start_month, $end])
            ->groupBy('sp')
            ->pluck('total', 'sp')
            ->all();

        // 日間端末別子ユーザー紹介数
        $sp_day_total_map = User::select(DB::raw('sp, count(*) as total'))
            ->where('friend_user_id', '>', 0)
            ->whereBetween('created_at', [$start_day, $end])
            ->groupBy('sp')
            ->pluck('total', 'sp')
            ->all();

        $month_total_map = [];
        $day_total_map = [];
        foreach ($device_map as $key => $value) {
            $month_total_map[$value] = $sp_month_total_map[$key] ?? 0;
            $day_total_map[$value] = $sp_day_total_map[$key] ?? 0;
        }

        // 月間アクション子ユーザー数
        $month_actioned_total = AffReward::whereBetween('actioned_at', [$start_month, $end])
            ->whereIn('status', [AffReward::REWARDED_STATUS, AffReward::WAITING_STATUS,
                AffReward::ACTIONED_STATUS])
            ->whereNotIn('asp_id', self::$EXCEPT_ASP_ID_LIST)
            ->whereIn('user_id', function ($query) use ($start_month, $end) {
                $query->select('id')
                    ->from('users')
                    ->where('friend_user_id', '>', 0)
                    ->whereBetween('created_at', [$start_month, $end]);
            })->count('user_id');
        // 月間アクション子ユーザー発生ポイント
        $month_actioned_point = AffReward::whereBetween('actioned_at', [$start_month, $end])
            ->whereIn('status', [AffReward::REWARDED_STATUS, AffReward::WAITING_STATUS,
                AffReward::ACTIONED_STATUS])
            ->whereNotIn('asp_id', self::$EXCEPT_ASP_ID_LIST)
            ->whereIn('user_id', function ($query) use ($start_month, $end) {
                $query->select('id')
                    ->from('users')
                    ->where('friend_user_id', '>', 0)
                    ->whereBetween('created_at', [$start_month, $end]);
            })->sum('point');
        $month_actioned_amount = floor($month_actioned_point / 10);

        // 月間ログイン子ユーザー数
        $month_logined_total = UserLogin::whereBetween('created_at', [$start_month, $end])
            ->whereIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('friend_user_id', '>', 0)
                    ->where('created_at', '>', Carbon::now()->startOfDay()->addYears(-1));
            })
            ->count(DB::raw('distinct user_id'));
        // 日間ログイン子ユーザー数
        $day_logined_total = UserLogin::whereBetween('created_at', [$start_day, $end])
            ->whereIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('friend_user_id', '>', 0)
                    ->where('created_at', '>', Carbon::now()->startOfDay()->addYears(-1));
            })
            ->count(DB::raw('distinct user_id'));

        //
        $friend_program_amount = $this->getFriendProgramAmount($start_month);

        // 月間ユーザー
        $friend_month_user_map = User::select(DB::raw('friend_user_id, count(*) as total'))
            ->where('friend_user_id', '>', 0)
            ->whereBetween('created_at', [$start_month, $end])
            ->orderByRaw('count(*) desc')
            ->groupBy('friend_user_id')
            ->take(20)
            ->pluck('total', 'friend_user_id')
            ->all();
        $month_user_map = [];
        foreach ($friend_month_user_map as $key => $value) {
            $month_user_map[User::getNameById($key)] = $value;
        }

        // 年間ユーザー
        $friend_year_user_map = User::select(DB::raw('friend_user_id, count(*) as total'))
            ->where('friend_user_id', '>', 0)
            ->whereBetween('created_at', [$start_year, $end])
            ->orderByRaw('count(*) desc')
            ->groupBy('friend_user_id')
            ->take(20)
            ->pluck('total', 'friend_user_id')
            ->all();
        $year_user_map = [];
        foreach ($friend_year_user_map as $key => $value) {
            $year_user_map[User::getNameById($key)] = $value;
        }

        // 月間IP
        $ip_map = UserEditLog::select(DB::raw('ip, count(*) as t'))
            ->whereBetween('created_at', [$start_month, $end])
            ->whereIn(DB::raw('(id, user_id)'), function ($query) {
                $query->select(DB::raw('min(id), user_id'))
                    ->from('user_edit_logs')
                    ->groupBy('user_id');
            })
            ->orderByRaw('COUNT(*) desc')
            ->groupBy('ip')
            ->havingRaw('COUNT(*) > ?', [3])
            ->pluck('t', 'ip')
            ->all();

        // ロックユーザー
        $lock_user_list = User::whereIn('status', [User::LOCK1_STATUS, User::LOCK2_STATUS])
            ->orderBy('id', 'asc')
            ->get();

        // メール送信を実行
        $options = ['start_year' => $start_year, 'start_month' => $start_month,
            'start_day' => $start_day, 'end' => $end,  'month_total_map' => $month_total_map,
            'day_total_map' => $day_total_map, 'month_user_map' => $month_user_map,
            'year_user_map' => $year_user_map, 'month_actioned_total' => $month_actioned_total,
            'month_actioned_amount' => $month_actioned_amount, 'month_logined_total' => $month_logined_total,
            'day_logined_total' => $day_logined_total, 'friend_program_amount' => $friend_program_amount,
            'ip_map' => $ip_map, 'except' => $except, 'lock_user_list' => $lock_user_list];
        try {
            $mailable = new \App\Mail\Notice('friend', $options);
            \Mail::send($mailable);
        } catch (\Exception $e) {
            \Log::info($e->getTraceAsString());
        }

        //
        $this->info('success');

        return 0;
    }
}
