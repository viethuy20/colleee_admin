<?php
namespace App\Console\UserEditLog;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\UserEditLog;

class BackupDelete extends BaseCommand
{
    protected $tag = 'user_edit_log:backup_delete';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_edit_log:backup_delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup file and delete user edit log';

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
        $file_path = $dir_path.DIRECTORY_SEPARATOR.'user_edit_logs_'.$start_at->format('YmdHis').'.csv';
        
        // 書き込みファイルを開く
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        // ヘッダー書き込み
        $file->fputcsv(['id', 'user_id', 'ip', 'ua', 'type', 'email', 'tel','created_at']);
        $last_id = 0;
        while (true) {
            // ユーザー更新一覧取得
            $user_edit_log_list = UserEditLog::whereNotIn('user_id', function ($query) {
                $query->select('id')->from('users');
            })
                ->where('id', '>', $last_id)
                ->orderBy('id', 'asc')
                ->take(5000)
                ->get();

            // 空の場合終了
            if ($user_edit_log_list->isEmpty()) {
                break;
            }

            foreach ($user_edit_log_list as $user_edit_log) {
                $last_id = $user_edit_log->id;
                $user_edit_log_data = $user_edit_log->only(['id', 'user_id', 'ip', 'ua', 'type', 'email', 'tel']);
                $user_edit_log_data[] = isset($user_edit_log->created_at) ?
                    $user_edit_log->created_at->format('Y-m-d H:i:s') : '';
                $file->fputcsv($user_edit_log_data);
            }

            // メモリ開放
            $user_edit_log_list = null;
            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

        // 書き込みファイルをクローズ
        $file = null;
        // ファイルクローズ待ちで0.2秒スリープ
        usleep(200000);

        // ユーザー削除実行
        \Artisan::call('user_edit_log:delete', ['file' => $file_path]);
        // ファイルを削除
        @unlink($file_path);
        
        //
        $this->info('success');
        return 0;
    }
}
