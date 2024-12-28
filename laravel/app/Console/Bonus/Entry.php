<?php
namespace App\Console\Bonus;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;
use App\UserPoint;
use App\FriendReferralBonusSchedule;
use App\UserFriendReferralBonusPoint;

/**
 * Description of Entry
 *
 * @author t_moriizumi
 */
class Entry extends BaseCommand
{
    protected $tag = 'bonus:entry';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:entry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Entry bonus';

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
     * ユーザー紹介ボーナスポイント付与.
     */
    private function addBonus()
    {
        $end_at = Carbon::now()
            ->startOfMonth()
            ->addSeconds(-1);
        $start_at = $end_at->copy()
            ->startOfMonth()
            ->addMonths(-2);

        $last_user_id = 1;
        while (true) {
            // ユーザー一覧を取得
            $user_list = User::whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->whereBetween('created_at', [$start_at, $end_at])
                ->whereIn('friend_user_id', function ($query) {
                    $query->select('id')
                        ->from('users')
                        ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS]);
                })
                ->where('entry_bonus', '=', 0)
                ->where('id', '>', $last_user_id)
                ->orderBy('id', 'asc')
                ->take(1000)
                ->get();

            // 終了
            if ($user_list->isEmpty()) {
                break;
            }

            $user_id_list = $user_list->pluck('id')->all();
            $last_user_id = max($user_id_list);

            // ユーザーが獲得したポイント数を集計
            $point_total_map = UserPoint::selectRaw('sum(diff_point + bonus_point) as point_total, user_id')
                ->where('diff_point', '>=', 0)
                ->where('bonus_point', '>=', 0)
                ->where('type', '<>', UserPoint::ROLLBACK_TYPE)
                ->whereIn('user_id', $user_id_list)
                ->groupBy('user_id')
                ->pluck('point_total', 'user_id')
                ->all();

            // ユーザー友達紹介報酬情報
            $friend_referral_bonus_point_total_list = UserFriendReferralBonusPoint::whereIn('user_id', $user_id_list)->get();
            $friend_referral_bonus_point_total_map = $friend_referral_bonus_point_total_list->pluck(null, 'user_id');

            foreach ($user_list as $user) {
                $point_total = $point_total_map[$user->id] ?? 0;
                if(isset($friend_referral_bonus_point_total_map[$user->id])) {
                    $friend_referral_bonus_point_total = $friend_referral_bonus_point_total_map[$user->id];
                } else {
                    // UserFriendReferralBonusPointにデータが存在しない場合
                    \Log::info('No UserFriendReferralBonusPoint UserID : ' . $user->id.',"'.date('Y-m-d H:i:s').'"');
                    continue;
                }

                // 獲得条件ポイント未満の場合
                if ($point_total < $friend_referral_bonus_point_total['reward_condition_point']) {
                    continue;
                }

                $user_point = UserPoint::getDefault(
                    $user->friend_user_id,
                    UserPoint::ENTRY_BONUS_TYPE,
                    0,
                    $friend_referral_bonus_point_total['friend_referral_bonus_point'], // 友達紹介報酬ポイント
                    $friend_referral_bonus_point_total['name'], // 友達紹介報酬スケジュール名
                );
                $user_point->parent_id = $user->id;

                // トランザクション処理
                $user_point->addPoint(function () use ($user) {
                    $user->entry_bonus = 1;
                    $user->save();
                    return true;
                });
            }
        }
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
        // ボーナス配布
        $this->addBonus();
        // 成功
        $this->info('success');
        return 0;
    }
}
