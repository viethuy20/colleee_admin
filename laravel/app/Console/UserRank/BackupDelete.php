<?php
namespace App\Console\UserRank;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\UserRank;

class BackupDelete extends BaseCommand
{
    protected $tag = 'user_rank:backup_delete';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_rank:backup_delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup file and delete user rank';

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

        // ディレクトリを取得
        $dir_path = config('path.backup');
        if (!file_exists($dir_path)) {
            mkdir($dir_path, '755');
        }

        $start_at = Carbon::now();

        // ファイルパスを作成
        $file_path = $dir_path.DIRECTORY_SEPARATOR.'user_ranks_'.$start_at->format('YmdHis').'.csv';

        // 書き込みファイルを開く
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        // ヘッダー書き込み
        $file->fputcsv(['id', 'user_id', 'rank', 'start_at', 'stop_at', 'created_at', 'updated_at']);
        $last_id = 0;
        while (true) {
            // ユーザーランク一覧取得
            $user_rank_list = UserRank::whereNotIn('user_id', function ($query) {
                $query->select('id')->from('users');
            })
                ->where('id', '>', $last_id)
                ->orderBy('id', 'asc')
                ->take(5000)
                ->get();

            // 空の場合終了
            if ($user_rank_list->isEmpty()) {
                break;
            }

            // ファイル書き込み
            foreach ($user_rank_list as $user_rank) {
                $last_id = $user_rank->id;
                $user_rank_data = $user_rank->only(['id', 'user_id', 'rank']);
                $user_rank_data[] = isset($user_rank->start_at) ? $user_rank->start_at->format('Y-m-d H:i:s') : '';
                $user_rank_data[] = isset($user_rank->stop_at) ? $user_rank->stop_at->format('Y-m-d H:i:s') : '';
                $user_rank_data[] = isset($user_rank->created_at) ? $user_rank->created_at->format('Y-m-d H:i:s') : '';
                $user_rank_data[] = isset($user_rank->updated_at) ? $user_rank->updated_at->format('Y-m-d H:i:s') : '';
                $file->fputcsv($user_rank_data);
            }
            
            // メモリ開放
            $user_rank_list = null;
            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

        // 書き込みファイルをクローズ
        $file = null;
        // ファイルクローズ待ちで0.2秒スリープ
        usleep(200000);

        // データが存在する場合、圧縮してデータ削除作業を行う
        if ($last_id > 0) {
            // 削除実行
            $this->call('user_rank:delete', ['file' => $file_path]);
        }
        // ファイルを削除
        @unlink($file_path);

        //
        $this->info('success');
        return 0;
    }
}
