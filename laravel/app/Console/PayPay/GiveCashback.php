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
 * PayPay付与
 */
class GiveCashback extends BaseCommand
{
    protected $tag = 'paypay:give_cashback';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypay:give_cashback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GiveCashback paypay';

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
        $rollback_error_list = config('paypay.rollback_error');
        $exceeded_limit_error_list = config('paypay.exceeded_limit_error');
        $retry_error_list = config('paypay.retry_error');
        $check_cashback_details_error_list = config('paypay.check_cashback_details_error');
        $time_start = -1;
        $count = 8;
        while ($count) {

            if ($count < 8) {
                sleep(10);
            }

            $count--;


            // ポイント交換申し込みを1件取得
            $exchange_request = ExchangeRequest::ofPayPay()
                ->ofWaiting($exchange_request_id)
                ->where('scheduled_at', '<=', Carbon::now())
                ->first();

            //なくなったら終了
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

            $cashback_id = $this->paypay_external->createExchangeRequestId($exchange_request);
            if($cashback_id === false){
                continue;
            }

            // ポイント交換申請実行
            $res = $this->paypay_external->execute(
                $exchange_accounts->number,
                $exchange_request->point,
                $exchange_request->id,
                $cashback_id
            );

            $merchantCashbackId = $this->paypay_external->getMerchantCashbackId();

            // エラーコード取得
            $error_code = $this->paypay_external->getErrorCode();
            $exchange_request->response_code = $this->paypay_external->getResponseCode();
            $exchange_request->response = $this->paypay_external->getBody();
            $exchange_request->request_level = 1;
            $exchange_request->requested_at = Carbon::now();

            // 正常終了の場合
            if ($res) { //paypay交換申請中statusに変える
                $exchange_request->paypayGiveCashbackRequest();
                continue;
            } else {

                //リクエストが重複してる可能性がある場合ステータスを確認して処理する
                if($error_code == 'VALIDATION_FAILED_EXCEPTION'){
                    sleep(2);
                    $cashback_detail_res = $this->paypay_external->checkCashbackDetails($exchange_request_id, $user->id);
                    $cashback_detail_status_code = $this->paypay_external->getDataStatusCode();

                    // 正常終了の場合
                    if ($cashback_detail_res && ($cashback_detail_status_code == 'SUCCESS' || $cashback_detail_status_code == 'ACCEPTED')) {
                        $error_code = $this->paypay_external->getErrorCode();
                        $exchange_request->response_code = $this->paypay_external->getResponseCode();
                        $exchange_request->response = $this->paypay_external->getBody();
                        $exchange_request->updated_at = Carbon::now();

                        $exchange_request->paypayGiveCashbackRequest();
                        continue;
                    }
                }


                if ($error_code == 'MAINTENANCE_MODE') { //メンテナンスの時
                    //メンテナンスの時は保留にしてステータス監視対象
                    $exchange_request->paypayRetryRequest();

                    $this->exchange_info->saveMaintenance();//メンテナンス用のデータを保存

                    $message = ['user_id' => $user->id, 'exchange_request_id' => $exchange_request_id];
                    $this->paypay_external->sendSystemMail(config('paypay.system_mail'), 'maintenance', $message);

                    continue;

                } elseif ($error_code && in_array($error_code, $exceeded_limit_error_list)) {
                    //リクエスト制限数超過の場合は再実行対象
                    continue;

                } elseif ($error_code && in_array($error_code, $retry_error_list)) {
                    //500エラーの場合はステータス監視対象
                    $exchange_request->paypayRetryRequest();

                    //システムエラーメール送信
                    $this->paypay_external->sendErrorMail($user->id,$exchange_request_id);

                    continue;

                } elseif (($error_code && in_array($error_code, $rollback_error_list))) {
                    //その他のエラーの場合は組戻し
                    $reverse_res = $this->paypay_external->reverseCashback($merchantCashbackId, $user->id, $exchange_request->point);
                    $exchange_request->rollbackRequest();
                    continue;

                } else {

                    //タイムアウトなどのエラーの場合はポイント付与処理のステータスを確認して処理する
                    sleep(2);
                    $res2 = $this->paypay_external->checkCashbackDetails($exchange_request_id, $user->id);
                    $status_code = $this->paypay_external->getStatusCode();
                    $error_code = $this->paypay_external->getErrorCode();
                    $data_status_code = $this->paypay_external->getDataStatusCode();

                    $exchange_request->response_code = $this->paypay_external->getResponseCode();
                    $exchange_request->response = $this->paypay_external->getBody();
                    $exchange_request->updated_at = Carbon::now();

                    // 正常終了の場合
                    if ($res2 && ($data_status_code == 'SUCCESS' || $data_status_code == 'ACCEPTED')) {
                        $exchange_request->paypayGiveCashbackRequest();
                        continue;
                    }

                    //トランザクションがない場合は再実行対象
                    if($error_code == 'TRANSACTION_NOT_FOUND'){
                        continue;
                    }

                    //予算残高が足りないときorメンテナンスの時
                    if ($error_code == 'NOT_ENOUGH_MONEY' || $error_code == 'MAINTENANCE_MODE') {
                        $exchange_request->paypayRetryRequest();

                        $this->exchange_info->saveMaintenance();//メンテナンス用のデータを保存

                        $message = ['user_id' => $user->id, 'exchange_request_id' => $exchange_request_id];
                        if ($error_code == 'MAINTENANCE_MODE') {
                            $this->paypay_external->sendSystemMail(config('paypay.system_mail'), 'maintenance', $message);
                        }
                        if ($error_code == 'NOT_ENOUGH_MONEY') {
                            $this->paypay_external->sendSystemMail(config('paypay.system_mail'), 'not_enough_money', $message);
                        }


                        continue;
                    }

                    //500エラーの場合はステータス監視対象
                    if ($error_code && in_array($error_code, $retry_error_list) || ($error_code && $error_code == 'INTERNAL_SERVICE_ERROR')) {
                        $exchange_request->paypayRetryRequest();

                        //システムエラーメール送信
                        $this->paypay_external->sendErrorMail($user->id,$exchange_request_id);

                        continue;
                    }
                }
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
