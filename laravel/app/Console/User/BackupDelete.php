<?php
namespace App\Console\User;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;

class BackupDelete extends BaseCommand
{
    protected $tag = 'user:backup_delete';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:backup_delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup file and delete user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function getFile(string $file_path) : \App\Csv\SplFileObject
    {
        // 書き込みファイルを開く
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        // ヘッダー書き込み
        $file->fputcsv([
            'id', 'name', 'old_id', 'email', 'sex', 'nickname', 'prefecture_id', 'status',
            'email_status', 'tel', 'friend_code', 'friend_user_id', 'blog', 'email_magazine',
            'email_point', 'promotion_id', 'sp', 'q1', 'q2', 'updated_admin_id', 'memo',
            'birthday', 'ticketed_at', 'actioned_at', 'created_at', 'updated_at', 'deleted_at',
        ]);
        return $file;
    }

    public static function writeFile($file, $user_list)
    {
        foreach ($user_list as $user) {
            // ユーザー情報を書き込み
            $user_data = $user->only([
                'id', 'name', 'old_id', 'email', 'sex', 'nickname', 'prefecture_id', 'status',
                'email_status', 'tel', 'friend_code', 'friend_user_id', 'blog', 'email_magazine',
                'email_point', 'promotion_id', 'sp', 'q1', 'q2', 'updated_admin_id', 'memo',
            ]);
            $user_data[] = isset($user->birthday) ? $user->birthday->format('Y-m-d') : '';
            $user_data[] = isset($user->ticketed_at) ? $user->ticketed_at->format('Y-m-d H:i:s') : '';
            $user_data[] = isset($user->actioned_at) ? $user->actioned_at->format('Y-m-d H:i:s') : '';
            $user_data[] = isset($user->created_at) ? $user->created_at->format('Y-m-d H:i:s') : '';
            $user_data[] = isset($user->updated_at) ? $user->updated_at->format('Y-m-d H:i:s') : '';
            $user_data[] = isset($user->deleted_at) ? $user->deleted_at->format('Y-m-d H:i:s') : '';
            $file->fputcsv($user_data);
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
        $admin_id = 0;

        // ディレクトリを取得
        $dir_path = config('path.backup');
        if (!file_exists($dir_path)) {
            mkdir($dir_path, '755');
        }

        $start_at = Carbon::now();
        $delete_at = $start_at->copy()
            ->startOfMonth()
            ->subYears(1);
        $force_delete_at = $start_at->copy()
            ->startOfMonth()
            ->subYears(2)
            ->subMonths(6);
        $actioned_at = $start_at->copy()
            ->startOfMonth()
            ->subYears(1);

        // ファイルパスを作成
        $file_path = $dir_path.DIRECTORY_SEPARATOR.'users_'.$start_at->format('YmdHis').'.csv';
        // 書き込みファイルを開く
        $file = self::getFile($file_path);

        $total = 0;
        
        $last_id = 0;
        while (true) {
            // ユーザー一覧取得
            $user_list = User::whereIn('status', [User::SELF_WITHDRAWAL_STATUS, User::OPERATION_WITHDRAWAL_STATUS])
                ->where('id', '>', $last_id)
                ->where('deleted_at', '<', $delete_at)
                ->where('test', '=', 0)
                ->orderBy('id', 'asc')
                ->take(5000)
                ->get();

            // 空の場合終了
            if ($user_list->isEmpty()) {
                break;
            }
            // ID取得
            $last_id = $user_list->last()->id;
            // 件数取得
            $total = $total + $user_list->count();
            // ファイル書き込み
            self::writeFile($file, $user_list);
            // メモリ解放
            $user_list = null;
            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

        $last_id = 0;
        while (true) {
            // ユーザー一覧取得
            $user_list = User::where('status', '=', User::FORCE_WITHDRAWAL_STATUS)
                ->where('id', '>', $last_id)
                ->where('deleted_at', '<', $force_delete_at)
                ->where('test', '=', 0)
                ->orderBy('id', 'asc')
                ->take(5000)
                ->get();
            // 空の場合終了
            if ($user_list->isEmpty()) {
                break;
            }
            // ID取得
            $last_id = $user_list->last()->id;
            // 件数取得
            $total = $total + $user_list->count();
            // ファイル書き込み
            self::writeFile($file, $user_list);
            // メモリ解放
            $user_list = null;
            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

        // 書き込みファイルをクローズ
        $file = null;
        // ファイルクローズ待ちで0.2秒スリープ
        usleep(200000);

        // データが存在する場合、圧縮してデータ削除作業を行う
        if ($total > 0) {
            // 削除実行
            $this->call('user:delete', ['file' => $file_path]);
        }
        // ファイルを削除
        @unlink($file_path);
        //
        $this->info('success');
        return 0;
    }
}
