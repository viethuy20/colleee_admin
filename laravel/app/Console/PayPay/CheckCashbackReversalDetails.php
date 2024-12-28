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
 * 1日1回実行する
 * PayPay付与キャンセルstatus確認
 */
class CheckCashbackReversalDetails extends BaseCommand {
    protected $tag = 'paypay:check_reversal_details';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypay:check_reversal_details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CheckCashbackReversalDetails paypay';

    private $paypay_external;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PayPay $paypay_external
    )
    {
        parent::__construct();
        $this->paypay_external = $paypay_external;
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

        // 前日中(updated_atで判定)のポイント交換キャンセル申し込みを1件取得
        $exchange_request_list = ExchangeRequest::ofPayPay()
        ->ofRollback()
        ->where('updated_at', '<', Carbon::today())
        ->where('updated_at', '>=', Carbon::yesterday())
        ->get();

        foreach($exchange_request_list as $exchange_request) {

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

            $exchange_request_id = $exchange_request->id;


            // ユーザー情報を取得
            $user = $exchange_request->user;


            //PayPay付与キャンセルstatus確認
            $res = $this->paypay_external->checkReverseCashback($exchange_request_id,$user->id);

            //キャンセルAPIのステータスを追記して保存
            $exchange_request->response_code = $exchange_request->response_code. '/CANCEL_STATUS:' .$this->paypay_external->getResponseCode();
            $exchange_request->timestamps = false;
            $exchange_request->save();


        }

        $this->info('success');

        return 0;
    }
}
