<?php
namespace App\Console\Kdol;

use Carbon\Carbon;
use App\Console\BaseCommand;
use App\External\Kdol;
use App\ExchangeInfo;
use App\ExchangeRequest;
use App\ExchangeAccounts;
use App\Services\CommonService;

class GiveCashback extends BaseCommand
{
    protected $tag = 'kdol:give_cashback';

    protected $signature = 'kdol:give_cashback';

    protected $description = 'GiveCashback kdol';

    private $kdol_external;
    private $exchange_info;
    private $commonService;

    public function __construct(
        Kdol $kdol_external,
        ExchangeInfo $exchange_info,
        CommonService $commonService
    )
    {
        parent::__construct();
        $this->kdol_external = $kdol_external;
        $this->exchange_info = $exchange_info;
        $this->commonService = $commonService;
    }

    public function handle()
    {
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
            $exchange_request = ExchangeRequest::ofKdol()
                ->OfWaiting()
                ->where('updated_at', '<', Carbon::now()->subMinutes(1))
                ->first();
                
            //なくなったら終了
            if (!isset($exchange_request->id))
            {
                break;
            }


            // 組戻しを確認
            if ($exchange_request->checkRollbackUser()) {
                continue;
            }

            // ユーザー情報を取得
            $user = $exchange_request->user;

            $exchange_accounts = ExchangeAccounts::select('number', 'data', 'user_id', 'id')
                ->from('exchange_accounts')
                ->where('user_id', '=', $user->id)
                ->where('type',  '=', ExchangeRequest::KDOL_TYPE)
                ->whereNull('deleted_at')
                ->get();

            if ($exchange_accounts->isEmpty()) {
                continue;
            }
            $exchange_accounts = $exchange_accounts->first();

            $cashback_id = $this->commonService->createExchangeRequestId($exchange_request);
            if($cashback_id === false){
                continue;
            }

            // ポイント交換申請実行
            $res = $this->kdol_external->cashbackPointRegist($exchange_request);

            // エラーコード取得
            $error_code = $this->kdol_external->getErrorCode();
            $exchange_request->response_code = $this->kdol_external->getResponseCode();
            $exchange_request->response = $this->kdol_external->getBody();
            $exchange_request->request_level = 1;
            $exchange_request->requested_at = Carbon::createFromTimestamp($this->kdol_external->getTransactionTime(),config('app.timezone'))->format('Y-m-d H:i:s');

            // 正常終了の場合
            if ($res) {
                $exchange_request->status = ExchangeRequest::EXCHANGE_WAITING_STATUS;
                $exchange_request->save();
                continue;
            } else {
                if($error_code === 3){//重複
                    $exchange_request->status = ExchangeRequest::EXCHANGE_WAITING_STATUS;
                    $exchange_request->save();
                    continue;

                }else{//エラーの場合は組み戻し
                    $exchange_request->rollbackRequest();
                    continue;
                }

            }
        
        
        $this->info('success');

        return 0;

        }
    }
}