<?php
namespace App\Console\Bonus;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;
use App\UserPoint;
use App\FriendReferralBonusSchedule;
use App\UserFriendReferralBonusPoint;

/**
 * Description of InsertFriendPoint
 *
 * @author t_ikeda
 */
class InsertFriendPoint extends BaseCommand
{
    protected $tag = 'insert_friend_point:entry';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert_friend_point:entry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'InsertFriendPoint';

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
     * ユーザー友達紹介報酬情報へ初期入力
     */
    private function addPointData()
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

            $insert_params = array();
            foreach ($user_list as $user) {
                $insert_params[] = array(
                    'user_id'                           => $user->id,
                    'friend_user_id'                    => $user->friend_user_id,
                    'friend_referral_bonus_schedule_id' => 1,
                    'name'                              => 'お友達紹介ボーナス',
                    'reward_condition_point'            => 3000,
                    'friend_referral_bonus_point'       => 5000,
                    'created_at'                        => date('Y-m-d H:i:s'),
                    'updated_at'                        => date('Y-m-d H:i:s'),
                );
            }

            UserFriendReferralBonusPoint::insert($insert_params);
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
        // ユーザー友達紹介報酬情報へ初期入力
        $this->addPointData();
        // 成功
        $this->info('success');
        return 0;
    }
}
