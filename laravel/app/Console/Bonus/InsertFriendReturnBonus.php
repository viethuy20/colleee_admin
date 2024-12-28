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
class InsertFriendReturnBonus extends BaseCommand {

    protected $tag = 'insert_friend_return_bonus:entry';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert_friend_return_bonus:entry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'friend return bonus';

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
    private function addPoint() {

        $base_time = Carbon::now();
        // 終了日
        $end = $base_time->copy()->endOfDay();
        // 会員登録開始日
        $entry_start = $end->copy()->startOfMonth()->addMonths(-11);
        // 成果開始日時
        $start = $end->copy()->startOfMonth();
        //
        $target_month = $end->format("Ym");
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
                ->where('parent_id', '=', $target_month)
                ->pluck('user_id')
                ->all();
            // remove user has bonus points in this month in userpoint
            $user_list = array_diff($user_id_list, $exist_user_id_list);
            //get list of friend user id
            $friend_user_id_list = User::whereIn('friend_user_id', $user_list)
                    ->whereBetween('created_at', [$entry_start, $end])
                    ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                    ->get();

            foreach ($friend_user_id_list as $user) {

                // ポイント数取得
                $point = UserPoint::where('user_points.user_id', $user->id)
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
                    continue;
                }
                // 前月の友達ユーザー登録数取得
                $friend_total = User::where('friend_user_id', $user->friend_user_id)
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

                //created or update user
                $record = UserReferralPointDetail::where('user_id', $user->id)
                    ->where('target_month', $target_month)
                    ->first();
                $new_data = [
                    'friend_return_bonus' => $bonus,
                ];
                if ($record) {
                    $record->fill($new_data);
                    if ($record->isDirty(['friend_return_bonus'])) {
                        $record->updated_at = now();
                        $record->save();
                    }
                } else {
                    UserReferralPointDetail::create(array_merge([
                        'user_id' => $user->id,
                        'target_month' => $target_month,
                        'friend_user_id' => $user->friend_user_id,
                        'friend_registered_at' => $user->created_at,
                        'referral_bonus' => 0
                    ], $new_data));
                }

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
