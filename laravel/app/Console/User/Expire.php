<?php
namespace App\Console\User;

use Carbon\Carbon;
use DB;

use App\AffAccount;
use App\AffReward;
use App\Console\BaseCommand;
use App\User;
use App\UserPoint;

class Expire extends BaseCommand
{
    protected $tag = 'user:expire';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire user';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // タグ作成
        $this->info('start');

        $admin_id = 0;

        // 所持ポイントが1以上のユーザーID一覧を取得
        $expire_point_user_id_list = UserPoint::select('user_id')
            ->distinct()
            ->where('point', '>', 0)
            ->whereIn(DB::raw('(user_id, id)'), function ($query) {
                $query->select(DB::raw('user_id, max(id)'))
                    ->from('user_points')
                    ->whereNotIn('user_id', function ($query) {
                        $query->select('id')
                            ->from('users')
                            ->whereIn(
                                'status',
                                [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS]
                            );
                    })
                    ->groupBy('user_id');
            })
            ->pluck('user_id')
            ->all();

        // ポイント失効を実行
        UserPoint::expirePoint($admin_id, $expire_point_user_id_list);

        // 負荷対策で0.2秒スリープ
        usleep(200000);

        // 削除されていないアフィリエイトアカウントのID一覧を取得
        $expire_aff_account_id_list = AffAccount::select('id')
            ->where('status', '=', 0)
            ->whereNotIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->whereIn(
                        'status',
                        [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS]
                    );
            })
            ->pluck('id')
            ->all();

        // アフィリエイトアカウントを停止
        if (!empty($expire_aff_account_id_list)) {
            AffAccount::whereIn('id', $expire_aff_account_id_list)
                ->update(['status' => 1, 'deleted_at' => Carbon::now()]);
        }

        // 負荷対策で0.2秒スリープ
        usleep(200000);

        // 削除されていない成果のID一覧を取得
        $expire_aff_reward_id_list = AffReward::select('id')
            ->where('status', '=', AffReward::ACTIONED_STATUS)
            ->whereNotIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->whereIn(
                        'status',
                        [User::SYSTEM_STATUS, User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS]
                    );
            })
            ->pluck('id')
            ->all();

        // 発生状態の成果を自動キャンセル
        if (!empty($expire_aff_reward_id_list)) {
            AffReward::where('status', '=', AffReward::ACTIONED_STATUS)
                ->whereIn('id', $expire_aff_reward_id_list)
                ->update(['status' => AffReward::AUTO_CANCELED_STATUS]);
        }

        //
        $this->info('success');
        return 0;
    }
}
