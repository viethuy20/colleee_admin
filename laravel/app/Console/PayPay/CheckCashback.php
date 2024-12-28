<?php
namespace App\Console\PayPay;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;
use App\ExchangeAccounts;

use App\External\PayPay;
use App\ExchangeInfo;
use Illuminate\Support\Facades\Mail;


/**
 * Description of Deposit
 * PayPay付与status確認
 */
class CheckCashback extends BaseCommand {
    protected $tag = 'paypay:check_cashback';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypay:check_cashback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CheckCashback paypay';

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
    )
    {
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

        $rollback_error_list = config('paypay.rollback_error');
        $exceeded_limit_error_list = config('paypay.exceeded_limit_error');
        $retry_error_list = config('paypay.retry_error');
        $check_cashback_details_error_list = config('paypay.check_cashback_details_error');
        
        $time_start = -1;
        $count = 8;
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
                ->ofPaypayWaiting()
                ->where('updated_at', '<', Carbon::now()->subMinutes(1))
                ->first();
                
            //なくなったら終了
            if (!isset($exchange_request->id))
            {
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

            $res2 = $this->paypay_external->checkCashbackDetails($exchange_request_id,$user->id);
            $data_status_code = $this->paypay_external->getDataStatusCode();

            $error_code = $this->paypay_external->getErrorCode();
            $exchange_request->response_code = $this->paypay_external->getResponseCode();
            $exchange_request->response = $this->paypay_external->getBody();
            $exchange_request->updated_at = Carbon::now();

            // 正常終了の場合
            if ($res2 && $data_status_code == 'SUCCESS') {//完了
                $exchange_request->approvalRequest();
                continue;
            }elseif ($res2 && $data_status_code == 'ACCEPTED'){//受け付け済み
                $exchange_request->save();
                continue;
            }

            if($error_code == 'NOT_ENOUGH_MONEY' || $error_code == 'MAINTENANCE_MODE'){//予算残高が足りないとき
                $exchange_request->paypayRetryRequest();

                $this->exchange_info->saveMaintenance();//メンテナンス用のデータを保存

                $message = ['user_id' => $user->id,'exchange_request_id' => $exchange_request_id];
                
                if($error_code == 'MAINTENANCE_MODE'){//メンテナンスの時
                    $this->paypay_external->sendSystemMail(config('paypay.system_mail'),'maintenance', $message);
                }
                if($error_code == 'NOT_ENOUGH_MONEY'){//予算残高が足りないとき
                    $this->paypay_external->sendSystemMail(config('paypay.system_mail'),'not_enough_money', $message);
                }

                continue;
            }


            //リクエスト制限数超過の場合は再実行対象
            if($error_code && in_array($error_code, $exceeded_limit_error_list)){
                sleep(10);
                continue;
            }

            //500エラーの場合はステータス監視対象
            if($error_code && in_array($error_code, $retry_error_list) || ($error_code && $error_code == 'INTERNAL_SERVICE_ERROR')){
                $exchange_request->paypayRetryRequest();

                //システムエラーメール送信
                $this->paypay_external->sendErrorMail($user->id,$exchange_request_id);

                continue;
            }

            //その他のエラーの場合は組戻し
            if(($error_code && in_array($error_code, $rollback_error_list)) || ($error_code && $error_code == 'TRANSACTION_NOT_FOUND')){
                $reverse_res = $this->paypay_external->reverseCashback($exchange_request_id,$user->id,$exchange_request->point);
                $exchange_request->rollbackRequest();
                continue;
            }


        }

        //
        $this->info('success');

        return 0;
    }
}
