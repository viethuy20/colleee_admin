<?php
namespace App\Console\LinePay;

use App\Console\BaseCommand;
use App\ExchangeRequest;
use App\External\LinePay;
use Carbon\Carbon;

/**
 * Description of Deposit
 *
 * @author t_moriizumi
 */
class Deposit extends BaseCommand {
    // LinePayを扱うためのプロパティ
    private $line_pay;

    protected $tag = 'line_pay:deposit';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line_pay:deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deposit line_pay';

    /**
     * Create a new command instance.
     * @param LinePay $line_pay LinePayオブジェクト
     * @return void
     */
    public function __construct(LinePay $line_pay)
    {
        $this->line_pay = $line_pay;
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

        $exchange_request_id = 0;
        $response_map = config('line_pay.response_code');
        $rollback_error_list = config('line_pay.rollback_error');
        while (true) {
            // ポイント交換申し込みを1件取得  fLinePay
            $exchange_request = ExchangeRequest::ofLinePay()
                ->ofWaiting($exchange_request_id)
                ->where('scheduled_at', '<=', Carbon::now())
                ->first();

            //　なくなったら終了
            if (!isset($exchange_request->id)) {
                break;
            }
            $exchange_request_id = $exchange_request->id;

            // 組戻しを確認
            if ($exchange_request->checkRollbackUser()) {
                continue;
            }

            // ユーザー情報を取得
            $user = $exchange_request->user;

            $line_account = $user->line_account;
            
            // LINE連携が一度もなしの場合はNULL、一度連携して解除した場合は空文字になる
            if (!$line_account) {
                continue;
            }
            
            // LinePayオブジェクト作成
            $request_body = [
                'referenceNo' => $line_account->referenceNo,
                'amount' => $exchange_request->yen,
                'currency' => 'JPY',
                'orderId' => $exchange_request->id,
            ];
            
            // 実行
            try {
                $result = $this->line_pay->execute($request_body);
            } catch (\Exception $e) {
                
                // エラー時の処理
                $this->error('LINE Pay Error Code:'.$e->getMessage());
                
                // エラーコードを取得
                $error_code = $e->getMessage();
                $exchange_request->response = $this->line_pay->getBody();
                $exchange_request->response_code = $error_code;
                $exchange_request->request_level = 1;
                $exchange_request->requested_at = Carbon::now();

                // 成功以外は組戻しを行う
                if (in_array($error_code, $rollback_error_list, true)) {
                    // 自動で組戻し
                    $exchange_request->rollbackRequest();
                    continue;
                }
            }

            // 成功時の処理
            $exchange_request->response = $this->line_pay->getBody();
            $exchange_request->request_level = 1;
            $exchange_request->requested_at = Carbon::now();

            // 正常終了の場合
            if ($result['returnCode'] === config('line_pay.success')) {
                $exchange_request->approvalRequest();
                continue;
            }
        }

        $this->info('success');

        return 0;
    }
}
