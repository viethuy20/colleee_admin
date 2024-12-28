<?php
namespace App\Console\UserPoint;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\External\MountManager;
use App\UserPoint;

class BackupDelete extends BaseCommand
{
    protected $tag = 'user_point:backup_delete';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_point:backup_delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup file and delete user point';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    private function getFile(string $file_path)
    {
        // ファイル作成
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        // ヘッダー出力
        $file->fputcsv([
            'id', 'user_id', 'diff_point', 'bonus_point', 'point', 'exchanged_point',
            'type', 'parent_id', 'title', 'admin_id', 'created_at', 'updated_at',
        ]);
        return $file;
    }

    private function writeFile($file, $user_point_list)
    {
        foreach ($user_point_list as $user_point) {
            $user_point_data = $user_point->only([
                'id', 'user_id', 'diff_point', 'bonus_point', 'point',
                'exchanged_point', 'type', 'parent_id', 'title', 'admin_id',
            ]);
            $user_point_data[] = isset($user_point->created_at) ? $user_point->created_at->format('Y-m-d H:i:s') : '';
            $user_point_data[] = isset($user_point->updated_at) ? $user_point->updated_at->format('Y-m-d H:i:s') : '';
            $file->fputcsv($user_point_data);
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
        
        // ディレクトリを取得
        $dir_path = config('path.backup');
        if (!file_exists($dir_path)) {
            mkdir($dir_path, '755');
        }
        
        $start_at = Carbon::today();
        // 期間を作成
        $reward_end_at = $start_at->copy()->startOfMonth()->subMonths(13)->endOfMonth();
        $end_at = $start_at->copy()->startOfMonth()->subMonths(7)->endOfMonth();

        // ファイルパスを作成
        $reward_file_path = $dir_path.DIRECTORY_SEPARATOR.'user_reward_points_'.$reward_end_at->format('Ym').'.csv';
        $file_path = $dir_path.DIRECTORY_SEPARATOR.'user_points_'.$end_at->format('Ym').'.csv';

        $group = [UserPoint::PROGRAM_TYPE, UserPoint::MONITOR_TYPE, UserPoint::OLD_PROGRAM_TYPE];

        // データが存在するか確認
        $exists = UserPoint::whereIn('type', $group)
            ->where('created_at', '<=', $reward_end_at)
            ->exists();
        if ($exists) {
            // ファイル作成
            $file = $this->getFile($reward_file_path);

            $last_id = 0;
            while (true) {
                $user_point_list = UserPoint::whereIn('type', $group)
                    ->where('created_at', '<=', $reward_end_at)
                    ->where('id', '>', $last_id)
                    ->orderBy('id', 'asc')
                    ->take(10000)
                    ->get();

                if ($user_point_list->isEmpty()) {
                    break;
                }

                // ファイル書き込み
                $this->writeFile($file, $user_point_list);
                // ID取得
                $last_id = $user_point_list->last()->id;

                // メモリ解放
                $user_point_list = null;
                // 負荷対策で0.2秒スリープ
                usleep(200000);
            }

            // 書き込みファイルをクローズ
            $file = null;
            // ファイルクローズ待ちで0.2秒スリープ
            usleep(200000);

            // ユーザー削除実行
            $this->call('user_point:delete', ['file' => $reward_file_path]);
            // ファイルを削除
            @unlink($reward_file_path);
        }
        
        // データが存在するか確認
        $exists = UserPoint::whereNotIn('type', $group)
            ->where('created_at', '<=', $end_at)
            ->exists();
        if ($exists) {
            // ファイル作成
            $file = $this->getFile($file_path);

            $last_id = 0;
            while (true) {
                $user_point_list = UserPoint::whereNotIn('type', $group)
                    ->where('created_at', '<=', $end_at)
                    ->where('id', '>', $last_id)
                    ->orderBy('id', 'asc')
                    ->take(10000)
                    ->get();

                if ($user_point_list->isEmpty()) {
                    break;
                }

                // ファイル書き込み
                $this->writeFile($file, $user_point_list);
                // ID取得
                $last_id = $user_point_list->last()->id;

                // メモリ解放
                $user_point_list = null;
                // 負荷対策で0.2秒スリープ
                usleep(200000);
            }

            // 書き込みファイルをクローズ
            $file = null;
            // ファイルクローズ待ちで0.2秒スリープ
            usleep(200000);

            // ユーザー削除実行
            $this->call('user_point:delete', ['file' => $file_path]);
            // ファイルを削除
            @unlink($file_path);
        }

        //
        $this->info('success');
        return 0;
    }
}
