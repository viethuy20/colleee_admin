<?php
namespace App\Console\Bonus;

use Carbon\Carbon;

use App\Console\BaseCommand;

use App\User;
use App\UserPoint;

/**
 * Description of Program
 *
 * @author t_moriizumi
 */
class Program extends BaseCommand {
    const POINT_TITLE = 'お友達獲得ボーナス';

    protected $tag = 'bonus:program';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Program bonus';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ポイントを配布.
     * @param int $user_id 配布対象ユーザーID
     * @param Carbon $end 終了日
     */
    private function addPoint(int $user_id, Carbon $end) {
        //
        $parent_id = $end->format("Ym");

        // 会員登録開始日
        $entry_start = $end->copy()->startOfMonth()->addMonths(-11);
        // 成果開始日時
        $start = $end->copy()->startOfMonth();

        // 友達ユーザーIDリストを取得
        $friend_user_id_list = User::where('friend_user_id', '=', $user_id)
                ->whereBetween('created_at', [$entry_start, $end])
                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->pluck('id')
                ->all();

        // ポイント数取得
        $point = UserPoint::whereIn('user_points.user_id', $friend_user_id_list)
            ->whereBetween('user_points.created_at', [$start, $end])
            ->where('user_points.type', '=', UserPoint::PROGRAM_TYPE)
            ->join('points', function($join) {
                $join->on('user_points.parent_id', '=', 'points.program_id')
                    ->whereRaw('user_points.created_at BETWEEN points.start_at AND points.stop_at');
            })
            ->sum('user_points.diff_point');

        if (config('bonus.date_release_change_rank') > $start->format('Y-mm')) $point = ceil($point / 10); 

        // 友達がボーナス対象広告を実行していない場合、ポイント配布しない
        if (empty($point)) {
            return;
        }

        // 前月の友達ユーザー登録数取得
        $friend_total = User::whereIn('id', $friend_user_id_list)
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->count();

        // 倍率計算
        $rate = 0.05;
        if ($friend_total > 50) {
            $rate = 0.1;
        } elseif($friend_total > 40) {
            $rate = 0.09;
        } elseif($friend_total > 30) {
            $rate = 0.08;
        } elseif($friend_total > 20) {
            $rate = 0.07;
        } elseif($friend_total > 10) {
            $rate = 0.06;
        }

        $bonus = ceil($point * $rate);

        //\Log::info($user_id.','.$point.','.$bonus.','.$rate.','.$friend_total.',"'.$start->format('Y-m-d H:i:s').'"');

        $user_point = UserPoint::getDefault($user_id, UserPoint::PROGRAM_BONUS_TYPE,
                    0, $bonus, self::POINT_TITLE);
        $user_point->parent_id = $parent_id;

        // トランザクション処理
        $user_point->addPoint(null, function() use ($user_id, $parent_id) {
            return !UserPoint::where('user_id', '=', $user_id)
                ->where('type', '=', UserPoint::PROGRAM_BONUS_TYPE)
                ->where('parent_id', '=', $parent_id)
                ->exists();
        });
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

        $base_time = Carbon::now()->startOfMonth();
        // 終了日
        $end = $base_time->copy()->addSeconds(-1);
        // 会員登録開始日
        $entry_start = $end->copy()->startOfMonth()->addMonths(-11);
        //
        $parent_id = $end->format("Ym");

        $last_user_id = 1;
        while (true) {
            // ユーザーIDを取得
            $user_id_list = User::whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->whereIn('id', function ($query) use($last_user_id, $entry_start, $end) {
                        $query->select('friend_user_id')
                                ->distinct()
                                ->from('users')
                                ->where('friend_user_id', '>', $last_user_id)
                                ->whereBetween('created_at', [$entry_start, $end])
                                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS]);
                })
                ->orderBy('id', 'asc')
                ->take(1000)
                ->pluck('id')
                ->all();

            // 空の場合は終了する
            if (empty($user_id_list)) {
                break;
            }
            $last_user_id = max($user_id_list);

            // 既にボーナスが存在するユーザーを抽出する
            $exist_user_id_list = UserPoint::whereIn('user_id', $user_id_list)
                ->where('type', '=', UserPoint::PROGRAM_BONUS_TYPE)
                ->where('parent_id', '=', $parent_id)
                ->pluck('user_id')
                ->all();

            foreach ($user_id_list as $user_id) {
                // ボーナスが存在するユーザーを除外する
                if (in_array($user_id, $exist_user_id_list)) {
                    continue;
                }

                // ポイントを配布
                $this->addPoint($user_id, $end);
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
