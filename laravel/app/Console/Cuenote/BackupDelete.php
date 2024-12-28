<?php
namespace App\Console\Cuenote;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;
use WrapPhp;

/**
 * Description of BackupDelete
 *
 * @author t_moriizumi
 */
class BackupDelete extends BaseCommand
{
    protected $tag = 'cuenote:backup_delete';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuenote:backup_delete {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup and delete cuenote user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '512M');
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

        $now = Carbon::now();

        $end_at = Carbon::today()->startOfMonth()
            ->addMonths(-12)
            ->addSeconds(-1);

        $dir_path = config('path.cuenote');

        // 読み込みファイルパス
        $rfile_path = $this->argument('file');
        $info = pathinfo($rfile_path);
        if ($info['extension'] == 'zip') {
            // ZIP圧縮されたファイルの場合
            $file_list = unzip($rfile_path, $dir_path);
            if (empty($file_list)) {
                $this->info('failed[File not found]');
                return 1;
            }
            @unlink($rfile_path);
            $rfile_path = $file_list[0];
        }

        // 書き込みファイルパス
        $wfile_path = $dir_path.DIRECTORY_SEPARATOR.'users_'.$now->format('YmdHis').$now->micro.'_nonaction.csv';
        // 読み込みファイルを作成
        $rfile = new \App\Csv\SplFileObject($rfile_path, 'r');
        // 書き込みファイルを開く
        $wfile = \App\Console\User\BackupDelete::getFile($wfile_path);
        $total = 0;
        while (true) {
            $user_id_list = [];
            while (true) {
                // パース
                if (($data = $rfile->fgetcsv()) === false || WrapPhp::count($data) < 2 || $data[1] == '') {
                    break;
                }
                $user_id_list[] = User::getIdByName($data[1]);

                if (WrapPhp::count($user_id_list) >= 5000) {
                    break;
                }
            }

            if (empty($user_id_list)) {
                break;
            }
            
            // 退会
            User::whereIn('id', $user_id_list)
                ->where('actioned_at', '<=', $end_at)
                ->where('status', '=', User::COLLEEE_STATUS)
                ->update(['updated_admin_id' => $admin_id, 'status' => User::OPERATION_WITHDRAWAL_STATUS,
                    'deleted_at' => $now]);
            // ユーザー一覧取得
            $user_list = User::whereIn('id', $user_id_list)
                ->where('status', '=', User::OPERATION_WITHDRAWAL_STATUS)
                ->get();

            $total += $user_list->count();

            // バックアップファイルを書き込む
            \App\Console\User\BackupDelete::writeFile($wfile, $user_list);

            // 進捗
            $this->info(sprintf("Delete users[%d]", $total));

            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

        // ファイルをクローズ
        $rfile = null;
        $wfile = null;
        
        // データが存在する場合、圧縮してデータ削除作業を行う
        if ($total > 0) {
            // 削除実行
            $this->call('user:delete', ['file' => $wfile_path]);
        }

        // ファイルを削除
        @unlink($rfile_path);
        @unlink($wfile_path);

        //
        $this->info('success');
        return 0;
    }
}
