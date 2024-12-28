<?php
namespace App\Console\Bonus;

use Carbon\Carbon;

use App\Console\BaseCommand;

use App\User;
use App\UserPoint;
use Illuminate\Support\Facades\DB;
use App\UserReferralPointDetail;
use App\FriendReferralBonusSchedule;
use App\UserFriendReferralBonusPoint;
/**
 * Description of Program
 *
 * @author t_moriizumi
 */
class InsertFriendReferralBonus extends BaseCommand {

    protected $tag = 'insert_friend_referral_bonus:entry';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert_friend_referral_bonus:entry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'friend referral bonus';

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

    private function addPoint(){
        $now = Carbon::now();
        $end_at = $now->copy()->endOfMonth();
        $start_at = $now->copy()->startOfMonth()->subMonths(2);
        $target_month = $end_at->format("Ym");

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
                ->where('referral_bonus', '=', 0)
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
                    // \Log::info('No UserFriendReferralBonusPoint UserID : ' . $user->id.',"'.date('Y-m-d H:i:s').'"');
                    continue;
                }

                // 獲得条件ポイント未満の場合
                if ($point_total < $friend_referral_bonus_point_total['reward_condition_point']) {
                    continue;
                }

                // insert or update
                $record = UserReferralPointDetail::where('user_id', $user->id)
                    ->where('target_month', $target_month)
                    ->first();

                $new_data = [
                    'referral_bonus' => $friend_referral_bonus_point_total['friend_referral_bonus_point'],
                ];
                if ($record) {
                    $record->fill($new_data);
                    $record->updated_at = now();
                    $record->save();
                } else {
                    UserReferralPointDetail::create(array_merge([
                        'user_id' => $user->id,
                        'target_month' => $target_month,
                        'friend_user_id' => $user->friend_user_id,
                        'friend_registered_at' => $user->created_at,
                        'friend_return_bonus' => 0
                    ], $new_data));
                    
                    
                }
                //save status user has referral bonus
                $user->referral_bonus = 1;
                $user->save();
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
        $this->addPoint();
        $this->info('success');

        return 0;
    }
}
