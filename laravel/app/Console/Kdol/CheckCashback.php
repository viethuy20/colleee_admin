<?php
namespace App\Console\Kdol;
use Carbon\Carbon;
use App\Console\BaseCommand;
use App\External\Kdol;
use App\ExchangeInfo;
use App\ExchangeRequest;
use App\ExchangeAccounts;
use App\Services\CommonService;



class CheckCashback extends BaseCommand
{
    protected $tag = 'kdol:check_cashback';

    protected $signature = 'kdol:check_cashback';

    protected $description = 'CheckCashback kdol';

    private $kdol_external;
    private $exchange_info;
    private $commonService;
    private $response_code;

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
        $this->response_code = config('kdol.response_chashback_code');
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
                ->OfExchangeWaiting()
                ->where('updated_at', '<', Carbon::now()->subMinutes(1))//1分前のデータを取得
                ->first();

            //なくなったら終了
            if (!isset($exchange_request->id)) {
                break;
            }

            $exchange_accounts = ExchangeAccounts::select('number', 'data', 'user_id', 'id')
            ->from('exchange_accounts')
            ->where('user_id', '=', $exchange_request->user_id)
            ->where('type',  '=', ExchangeRequest::KDOL_TYPE)
            ->whereNull('deleted_at')
            ->get();

            if ($exchange_accounts->isEmpty()) {
                continue;
            }
            $exchange_accounts = $exchange_accounts->first();


            $res = $this->kdol_external->checkCashbackStatus($exchange_request);

            // エラーコード取得
            $statusDataCode = $this->kdol_external->getDataStatusCode();
            $exchange_request->response_code = $exchange_request->response_code.':'.$this->kdol_external->getResponseCode();

            // 正常終了の場合
            if ($res) {
                 $exchange_request->response_code = $exchange_request->response_code.':'.$this->response_code[$statusDataCode];

                 if((int)$statusDataCode===1){//受付
                    $exchange_request->save();

                }elseif((int)$statusDataCode===2){//成功
                    $exchange_request->approvalRequest();

                }elseif((int)$statusDataCode===3){//失敗
                    $exchange_request->rollbackRequest();

                }
                continue;
            }else{
                $exchange_request->response_code = $exchange_request->response_code.':'.$this->kdol_external->getResponseCode();
                $exchange_request->save();
                continue;
            }
        }
    }
}