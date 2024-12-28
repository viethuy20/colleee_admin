<?php
namespace App\Console\BankAccount;

use Carbon\Carbon;

use App\BankAccount;
use App\Console\BaseCommand;

class BackupDelete extends BaseCommand
{
    protected $tag = 'bank_account:backup_delete';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank_account:backup_delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup file and delete bank account';

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
        $file_path = $dir_path.DIRECTORY_SEPARATOR.'bank_accounts_'.$start_at->format('YmdHis').'.csv';

        // 書き込みファイルを開く
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        // ヘッダー書き込み
        $file->fputcsv([
            'id', 'user_id', 'first_name', 'last_name', 'bank_code', 'branch_code', 'number',
            'first_name_kana', 'last_name_kana', 'status', 'created_at', 'updated_at', 'deleted_at',
        ]);
        $last_id = 0;
        while (true) {
            // 銀行口座一覧取得
            $bank_account_list = BankAccount::whereNotIn('user_id', function ($query) {
                $query->select('id')->from('users');
            })
                ->where('id', '>', $last_id)
                ->orderBy('id', 'asc')
                ->take(5000)
                ->get();

            // 空の場合終了
            if ($bank_account_list->isEmpty()) {
                break;
            }

            foreach ($bank_account_list as $bank_account) {
                $last_id = $bank_account->id;
                $bank_account_data = $bank_account->only([
                    'id', 'user_id', 'first_name', 'last_name', 'bank_code', 'branch_code',
                    'number', 'first_name_kana', 'last_name_kana', 'status',
                ]);
                $bank_account_data[] = isset($bank_account->created_at) ?
                    $bank_account->created_at->format('Y-m-d H:i:s') : '';
                $bank_account_data[] = isset($bank_account->updated_at) ?
                    $bank_account->updated_at->format('Y-m-d H:i:s') : '';
                $bank_account_data[] = isset($bank_account->deleted_at) ?
                    $bank_account->deleted_at->format('Y-m-d H:i:s') : '';
                $file->fputcsv($bank_account_data);
            }

            // メモリ開放
            $bank_account_list = null;
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
            $this->call('bank_account:delete', ['file' => $file_path]);
        }
        // ファイルを削除
        @unlink($file_path);

        //
        $this->info('success');
        return 0;
    }
}
