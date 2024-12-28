<?php
namespace App\Console\DPoint;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;
use App\ExchangeAccounts;

use App\External\DPoint;


/**
 * Description of Deposit
 *
 * @author y_saito
 */
class Grant extends BaseCommand {
    protected $tag = 'd_point:grant';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'd_point:grant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant d_point';

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

        $request_error = 0;
        $exchange_request_id = 0;
        $rollback_error_list = config('d_point.rollback_error');
        $duplicate_error_list = config('d_point.duplicate_error');
        $break_error_list = config('d_point.break_error');

        while (true) {
            // ポイント交換申し込みを1件取得
            $exchange_request = ExchangeRequest::ofDPoint()
                ->ofWaiting($exchange_request_id)
                ->where('scheduled_at', '<=', Carbon::now())
                ->first();

            //　なくなったら終了
            if (!isset($exchange_request->id)) 
            {
                break;
            }

            $exchange_request_id = $exchange_request->id;

            // 組戻しを確認
            if ($exchange_request->checkRollbackUser()) {
                continue;
            }

            // ユーザー情報を取得
            $user = $exchange_request->user;

            $exchange_accounts = ExchangeAccounts::select('number', 'data')
                ->from('exchange_accounts')
                ->where('user_id', '=', $user->id)
                ->where('type',  '=', ExchangeRequest::D_POINT_TYPE)
                ->whereNull('deleted_at')
                ->get();

            if ($exchange_accounts->isEmpty()) {
                continue;
            }

            $exchange_accounts = $exchange_accounts->first();

            // スタブ
           // $exchange_accounts->number = '001320587604';

            // DPointオブジェクト作成
            $d_point = DPoint::getGrant(
                $exchange_accounts->number,
                $exchange_request->created_at,
                $exchange_request->id,
                $exchange_request->point);

            // 実行
            $res = $d_point->execute();

            $this->info('Dpoint Request Data');
            $this->info($d_point->getRequest());

            $this->info('Dpoint Response Data');
            $this->info($d_point->getBody());

            // エラーコード取得
            $error_code = $d_point->getErrorCode();
            $status_code = $d_point->getStatusCode();

            $exchange_request->response_code = $error_code;
            $exchange_request->response = $d_point->getBody();
            $exchange_request->request_level = 1;
            $exchange_request->requested_at = Carbon::now();
            
            // 正常終了の場合
            if ($res) {
                $exchange_request->approvalRequest();
                continue;
            }

            // 組戻しエラーの場合
            if (in_array($error_code, $rollback_error_list, true)) {
                // 自動で組戻し
                var_dump($error_code);
                $exchange_request->rollbackRequest();
                continue;
            }

            // 重複エラーの場合
            if (in_array($error_code, $duplicate_error_list, true)) {
                $this->info('重複エラー(正常系扱い)(error code:'. $error_code. ')');
                $exchange_request->approvalRequest();
                continue;
            }
            
            // 停止エラーの場合 (次回再実行対象)
            if (in_array($error_code, $break_error_list, true)) {
                $this->info('システムエラー(error code:'. $error_code. ')');
                break;
            }

            // ステータスコードが200以外の場合
            if ($status_code != '200' && !empty($status_code)) {
                $this->info('システムエラー(status code:'. $status_code. ')');
                break;
            }

            // エラー回数インクリメント
            $request_error = $request_error + 1;

            // 一定回数を超えた場合はバッチを終了
            if ($request_error >= 10) {
                $this->info('System is instability.');
                break;
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
