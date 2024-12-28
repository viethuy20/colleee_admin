<?php

namespace App\Console\PayPay;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;
use App\ExchangeAccounts;

use App\External\PayPay;
use App\ExchangeInfo;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaypayAlert;



/**
 * Description of Deposit
 * PayPay付与status確認500エラーリトライ用
 */
class CheckRetryCashback extends BaseCommand
{
    protected $tag = 'paypay:check_retry_cashback';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypay:check_retry_cashback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CheckRetryCashback paypay';

    private $paypay_external;
    private $exchange_info;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PayPay $paypay_external,
        ExchangeInfo $exchange_info
    ) {
        parent::__construct();
        $this->paypay_external = $paypay_external;
        $this->exchange_info = $exchange_info;
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

        $time_start = -1;
        $count = 5;
        while ($count) {
            $count--;

            //TPS制御
            $time_end = microtime(true);
            if($time_start>0){
                $time = $time_end - $time_start;
                if ($time < 1000000) {
                    $sleep_time = 1000000 - $time;
                    usleep($sleep_time);
                }
            }
            $time_start = microtime(true);


            // ポイント交換申し込みを1件取得
            $exchange_request = ExchangeRequest::ofPayPay()
                ->ofPaypayRetry()
                ->where('scheduled_at', '<=', Carbon::now())
                ->where('updated_at', '<=', Carbon::now()->subHours(1))
                ->first();

            //なくなったら終了
            if (!isset($exchange_request->id)) {
                break;
            }

            $exchange_request_id = $exchange_request->id;


            // ユーザー情報を取得
            $user = $exchange_request->user;


            $exchange_accounts = ExchangeAccounts::select('number', 'data')
                ->from('exchange_accounts')
                ->where('user_id', '=', $user->id)
                ->where('type',  '=', ExchangeRequest::PAYPAY_TYPE)
                ->whereNull('deleted_at')
                ->get();

            if ($exchange_accounts->isEmpty()) {
                continue;
            }

            $exchange_accounts = $exchange_accounts->first();

            $res2 = $this->paypay_external->checkCashbackDetails($exchange_request_id, $user->id);
            $error_code = $this->paypay_external->getErrorCode();
            $data_status_code = $this->paypay_external->getDataStatusCode();

            $exchange_request->response_code = $this->paypay_external->getResponseCode();
            $exchange_request->response = $this->paypay_external->getBody();
            $exchange_request->updated_at = Carbon::now();

            // 正常終了の場合
            if ($res2 && $data_status_code == 'SUCCESS') { //完了
                $exchange_request->approvalRequest();

                //システムメール送信
                $this->paypay_external->sendCheckRetryCashBackMail($user->id, $exchange_request_id, 'SUCCESS');

                continue;
            } elseif ($res2 && $data_status_code == 'ACCEPTED') { //受け付け済み
                $exchange_request->paypayGiveCashbackRequest();

                //システムメール送信
                $this->paypay_external->sendCheckRetryCashBackMail($user->id, $exchange_request_id, 'ACCEPTED');

                continue;
            } else {

                $exchange_request->save();

                if ($error_code == 'NOT_ENOUGH_MONEY') { //予算残高が足りないとき

                    $this->exchange_info->saveMaintenance();//メンテナンス用のデータを保存
                    $message = ['user_id' => $user->id,'exchange_request_id' => $exchange_request_id];
                    $this->paypay_external->sendSystemMail(config('paypay.system_mail'), 'not_enough_money', $message);
                }

                //キャッシュバックトランザクションの処理中に内部サービスエラーが発生した場合
                if($error_code == 'INTERNAL_SERVICE_ERROR'){
                    $reverse_res = $this->paypay_external->reverseCashback($exchange_request_id,$user->id,$exchange_request->point);
                    $exchange_request->rollbackRequest();
                }

                continue;
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
