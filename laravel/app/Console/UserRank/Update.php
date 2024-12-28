<?php
namespace App\Console\UserRank;

use Carbon\Carbon;
use DB;

use App\Console\BaseCommand;
use App\User;
use App\UserPoint;
use App\UserRank;

class Update extends BaseCommand
{
    protected $tag = 'user_rank:update';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_rank:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user_rank';

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
        
        $base_time = Carbon::now()->startOfMonth();
        
        $rank_start_at = $base_time->copy()->addHours(4);
        $user_created_at = $base_time->copy()->addDays(-90);
        $cur_stop_at = $rank_start_at->copy()->addSecond(-1);
        $max_stop_at = Carbon::parse('9999-12-31 23:59:59');
        
        $start_at = $rank_start_at->copy()->addMonths(-6)->startOfMonth();
        $end_at = $base_time->copy()->addSecond(-1);
        
        $last_user_id = 1;
        while (true) {
            // ユーザー情報を取得
            $user_map = User::select('id', 'created_at')
                ->where('id', '>', $last_user_id)
                ->orderBy('id', 'asc')
                ->take(1000)
                ->pluck('created_at', 'id')
                ->all();
            // ユーザーが空になった場合
            if (empty($user_map)) {
                break;
            }
            
            // ユーザーIDリスト取得
            $user_id_list = array_keys($user_map);
            //
            $last_user_id = max($user_id_list);
            
            // ユーザーが参加した広告件数を取得
            $user_point_ad_map = UserPoint::select(DB::raw(
                'count(id) as aff_total, user_id'
            ))
                ->whereIn('user_id', $user_id_list)
                ->whereBetween('created_at', [$start_at, $end_at])
                ->whereIn('type', [UserPoint::PROGRAM_TYPE, UserPoint::OLD_PROGRAM_TYPE, UserPoint::MONITOR_TYPE])
                ->groupBy('user_id')
                ->orderBy('user_id', 'asc')
                ->get()
                ->keyBy('user_id')
                ->all();
 

            // ユーザーが獲得した成果を確認
            $user_point_map = UserPoint::select(DB::raw(
                'sum(diff_point+bonus_point) as point_total, user_id'
            ))
                ->whereIn('user_id', $user_id_list)
                ->whereBetween('created_at', [$start_at, $end_at])
                ->whereIn('type', [UserPoint::PROGRAM_TYPE, UserPoint::MONITOR_TYPE, UserPoint::QUESTION_TYPE, UserPoint::REVIEW_TYPE, UserPoint::OLD_PROGRAM_TYPE, UserPoint::SP_PROGRAM_TYPE, UserPoint::SP_PROGRAM_WITH_REWARD_TYPE, UserPoint::BIRTYDAY_BONUS_TYPE, UserPoint::PROGRAM_BONUS_TYPE, UserPoint::ENTRY_BONUS_TYPE])
                ->groupBy('user_id')
                ->orderBy('user_id', 'asc')
                ->get()
                ->keyBy('user_id')
                ->all();
            
            // ユーザーのランクを取得
            $cur_user_rank_map = UserRank::whereIn('user_id', $user_id_list)
                ->ofTerm($rank_start_at)
                ->get()
                ->keyBy('user_id')
                ->all();

            // 停止させるランクIDリスト
            $stop_user_rank_id_list = [];
            // 追加するユーザーランク
            $insert_user_rank_list = [];
            
            foreach ($user_map as $user_id => $created_at) {
                // 成果を格納
                $user_rank_value = 0;
                if (isset($user_point_map[$user_id])) {
                    $point_total = $user_point_map[$user_id]['point_total'];
                    $aff_total = isset($user_point_ad_map[$user_id]) ? $user_point_ad_map[$user_id]['aff_total'] : 0;
                    if ($point_total >= 10000 && $aff_total >= 5) {
                        // ゴールド
                        $user_rank_value = 3;
                    } elseif ($point_total >= 2000 || $aff_total >= 3) {
                        // シルバー
                        $user_rank_value = 2;
                    }
                }
                
                // 既存のランクが存在した場合
                if (isset($cur_user_rank_map[$user_id])) {
                    $cur_user_rank = $cur_user_rank_map[$user_id];
                    
                    // 今現在のランクと次のランクが変わらない場合、何もしない
                    if ($cur_user_rank->rank == $user_rank_value) {
                        continue;
                    }

                    // レート変更に伴うイレギュラー処理
                    if (!empty(config('bonus.date_release_change_rank')) && $cur_user_rank->rank > $user_rank_value &&
                        (Carbon::now()->subMonths(6)->format('Y-m') <= config('bonus.date_release_change_rank'))) {
                        continue;
                    }
                    // 今現在のランクを停止させる
                    $stop_user_rank_id_list[] = $cur_user_rank->id;
                }
                
                // 通常ランクの場合、ランクは登録しない
                if ($user_rank_value == 0) {
                    continue;
                }

                // 新ランク情報を作成
                $insert_user_rank_list[] = ['user_id' => $user_id, 'rank' => $user_rank_value,
                    'start_at' => $rank_start_at, 'stop_at' => $max_stop_at,
                    'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
            }
            
            // 更新処理
            UserRank::updateRank($cur_stop_at, $stop_user_rank_id_list, $insert_user_rank_list);
        }

        //
        $this->info('success');
        return 0;
    }
}
