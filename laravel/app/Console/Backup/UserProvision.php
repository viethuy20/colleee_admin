<?php
namespace App\Console\Backup;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\External\MountManager;

class UserProvision extends BaseCommand
{
    protected $tag = 'backup:user_provision';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:user_provision {months?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make user_provision backup';

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

        $dir_path = config('path.backup');
        if (!file_exists($dir_path)) {
            mkdir($dir_path, '755');
        }

        $months = $this->argument('months') ?? 1;

        // 期間を作成
        $start_at = Carbon::today()->startOfMonth()->subMonths($months);
        $end_at = $start_at->copy()->endOfMonth();

        // ファイルパスを作成
        $file_path = $dir_path.DIRECTORY_SEPARATOR.'pi_'.$start_at->format('Ym').'.csv';

        // ファイル作成
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        // ヘッダー出力
        $file->fputcsv(['提供する相手方', 'ColleeeユーザーID', '提供項目', '提供年月日', '同意']);

        // ASP取得
        $asp_map = \App\Asp::pluck('company', 'id')->all();

        // 広告
        $last_id = 0;
        while (true) {
            $external_link_list = \App\ExternalLink::select('id', 'asp_id', 'user_id', 'created_at')
                ->where('id', '>', $last_id)
                ->whereBetween('created_at', [$start_at, $end_at])
                ->orderBy('id', 'asc')
                ->take(10000)
                ->get();

            if ($external_link_list->isEmpty()) {
                break;
            }

            foreach ($external_link_list as $external_link) {
                $last_id = $external_link->id;
                $external_link_data = [$asp_map[$external_link->asp_id],
                    \App\User::getNameById($external_link->user_id), 'ColleeeユーザーID',
                    $external_link->created_at->format('Y-m-d'), '本人が利用規約に同意'];
                $file->fputcsv($external_link_data);
            }
        }

        // ファンくる送信
        $last_id = 0;
        while (true) {
            $aff_account_list = \App\AffAccount::select('id', 'user_id', 'created_at')
                ->where('id', '>', $last_id)
                ->whereBetween('created_at', [$start_at, $end_at])
                ->where('type', '=', \App\AffAccount::FANCREW_TYPE)
                ->orderBy('id', 'asc')
                ->take(10000)
                ->get();

            if ($aff_account_list->isEmpty()) {
                break;
            }

            foreach ($aff_account_list as $aff_account) {
                $last_id = $aff_account->id;
                $aff_account_data = ['株式会社ROI', \App\User::getNameById($aff_account->user_id),
                    '性別,生年月日', $aff_account->created_at->format('Y-m-d'), '本人が利用規約に同意'];
                $file->fputcsv($aff_account_data);
            }
        }

        // ポイント交換
        $exchange_request_type_map = [
            \App\ExchangeRequest::BANK_TYPE => [
                'label' => 'GMOペイメントゲートウェイ株式会社',
                'column' => 'ポイント交換申し込み番号,振込先銀行番号,振込先支店番号,振込先口座番号,振込先口座カナ名義,振込依頼金額'
            ],
        ];
        $exchange_request_type_list = array_keys($exchange_request_type_map);
        $last_id = 0;
        while (true) {
            $exchange_request_list = \App\ExchangeRequest::select('id', 'type', 'user_id', 'requested_at')
                ->where('id', '>', $last_id)
                ->whereBetween('requested_at', [$start_at, $end_at])
                ->whereIn('type', $exchange_request_type_list)
                ->whereNotNull('requested_at')
                ->orderBy('id', 'asc')
                ->take(10000)
                ->get();

            if ($exchange_request_list->isEmpty()) {
                break;
            }

            foreach ($exchange_request_list as $exchange_request) {
                $last_id = $exchange_request->id;
                $exchange_request_type = $exchange_request_type_map[$exchange_request->type];
                $exchange_request_data = [$exchange_request_type['label'],
                    \App\User::getNameById($exchange_request->user_id), $exchange_request_type['column'],
                    $exchange_request->requested_at->format('Y-m-d'), '本人が利用規約に同意'];
                $file->fputcsv($exchange_request_data);
            }
        }

        // .money
        $last_id = 0;
        while (true) {
            $history_list = \App\History::select('id', 'data', 'created_at')
                ->where('id', '>', $last_id)
                ->whereBetween('created_at', [$start_at, $end_at])
                ->where('type', '=', \App\History::DOT_MONEY_TYPE)
                ->orderBy('id', 'asc')
                ->take(10000)
                ->get();

            if ($history_list->isEmpty()) {
                break;
            }

            foreach ($history_list as $history) {
                $last_id = $history->id;
                $history_data = ['株式会社ドットマネー', \App\User::getNameById($history->data->user_id),
                    'ColleeeユーザーID', $history->created_at->format('Y-m-d'), '本人が利用規約に同意'];
                $file->fputcsv($history_data);
            }
        }

        // オスティアリーズ
        $last_id = 0;
        while (true) {
            $user_edit_log_list = \App\UserEditLog::select('id', 'user_id', 'created_at')
                ->where('id', '>', $last_id)
                ->whereBetween('created_at', [$start_at, $end_at])
                ->orderBy('id', 'asc')
                ->take(10000)
                ->get();

            if ($user_edit_log_list->isEmpty()) {
                break;
            }

            foreach ($user_edit_log_list as $user_edit_log) {
                $last_id = $user_edit_log->id;
                $user_edit_log_data = ['株式会社オスティアリーズ', \App\User::getNameById($user_edit_log->user_id),
                    '電話番号', $user_edit_log->created_at->format('Y-m-d'), '本人が利用規約に同意'];
                $file->fputcsv($user_edit_log_data);
            }
        }

        // 書き込みファイルをクローズ
        $file = null;
        // ファイルクローズ待ちで0.2秒スリープ
        usleep(200000);
        // ZIP圧縮してファイルをマウント
        MountManager::zipMount(MountManager::USER_PROVISION_TYPE, $file_path);
        // ファイルを削除
        @unlink($file_path);
        //
        $this->info('success');
        return 0;
    }
}
