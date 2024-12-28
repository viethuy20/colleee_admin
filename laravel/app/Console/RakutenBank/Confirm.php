<?php
namespace App\Console\RakutenBank;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;

use App\External\RakutenBank;

/**
 * Description of Confirm
 *
 * @author t_moriizumi
 */
class Confirm extends BaseCommand {
    protected $tag = 'rakuten_bank:confirm';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rakuten_bank:confirm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Confirm rakuten_bank';
    
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
     * 最終承認実行.
     * @param ExchangeRequest $exchange_request 申し込み情報
     */
    private function approvalExchangeRequest(ExchangeRequest $exchange_request) {
        $exchange_request->request_level = 2;
        $exchange_request->confirmed_at = Carbon::now();
        $exchange_request->response_code = '000';
        $exchange_request->approvalRequest();
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
        
        while(true) {
            // 銀行振り込み申し込みを1件取得
            $exchange_request = ExchangeRequest::ofBank()
                ->where('status', '=', 0)
                ->whereNull('confirmed_at')
                ->where('request_level', '=', 1)
                ->where('scheduled_at', '<=', Carbon::now())
                ->where('id', '>', $exchange_request_id)
                ->orderBy('id', 'asc')
                ->first();
            
            //　なくなったら終了
            if (!isset($exchange_request->id)) {
                break;
            }
            $exchange_request_id = $exchange_request->id;
            
            // 楽天銀行振込結果照会オブジェクト作成
            $rakuten_bank = RakutenBank::getConfirm($exchange_request->number);
            
            // 実行
            $res = $rakuten_bank->execute();
            
            $response = json_decode($exchange_request->response);
            
            if (!$res) {
                $response->confirm_total = $response->confirm_total + 1;
                $exchange_request->response = json_encode($response);
                // ネットワークエラーの場合
                if ($response->confirm_total >= 3) {
                    // エラー上限を超えた場合、管理画面で承認,却下作業をさせる
                    $exchange_request->status = 3;
                } else {
                    // 3分後に実行
                    $exchange_request->scheduled_at = Carbon::now()->addMinutes(3);
                }
                
                $exchange_request->save();
                continue;
            }
            
            $response->confirm[] = $rakuten_bank->getBody();
            
            // 振込依頼書理結果コード取得
            $status = $rakuten_bank->getResponse(RakutenBank::STAUS_RESPONSE);
            $transfer_status = $rakuten_bank->getResponse(RakutenBank::TRANSFER_STAUS_RESPONSE);
            
            // システムモードエラーの場合はバッチを終了
            if ($status == '07' || $status == '99') {
                $this->info('error code:'.$status);
                return 0;
            }

            $exchange_request->response = json_encode($response);
            
            // 該当データなしで交換申し込み期間が上限に達していない場合は終了
            if ($status == '20' && $exchange_request->created_at->gt(Carbon::now()->addDays(14))) {
                // 保存だけする
                $exchange_request->save();
                continue;
            }
            
            // 異常終了の場合は終了
            if ($status != '00' || $transfer_status != '00') {
                // 自動で組戻し
                $exchange_request->request_level = 2;
                $exchange_request->confirmed_at = Carbon::now();
                $exchange_request->response_code = ($transfer_status != '00' ? '1'.$transfer_status : '0'.$status);
                $exchange_request->rollbackRequest();
                continue;
            }
            
            // 銀行口座情報取得
            $account = $exchange_request->bank_account;

            // 楽天銀行の場合、リアルタイムなので終了
            if ($account->bank_code == '0036') {
                // 正常終了状態へ移行
                $this->approvalExchangeRequest($exchange_request);
                continue;
            }

            // 振込予定日取得
            $scheduled = $rakuten_bank->getResponse(RakutenBank::SCHEDULED_AT_RESPONSE);
            // 振込予定日が取得できなかった場合
            if (!isset($scheduled) || preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $scheduled, $matches) != 1) {
                $this->info('Carbon parse error:'.$scheduled);
                // 管理画面で承認,却下作業をさせる
                $exchange_request->status = 3;
                $exchange_request->save();
                continue;
            }

            try {
                // 振込予定日を取得
                $scheduled_at = Carbon::parse(sprintf("%04d-%02d-%02d 00:00:00", $matches[1], $matches[2], $matches[3]));

                // 振込予定日が今日より前の場合、正常に振込状態で終了
                if (Carbon::today()->gt($scheduled_at)) {
                    // 正常終了状態へ移行
                    $this->approvalExchangeRequest($exchange_request);
                    continue;
                }

                // 振込予定日の次の日にまた実行させる
                $scheduled_at = $scheduled_at->copy()->addDays(1);
                $scheduled_at->hour = 11;
                $scheduled_at->minute = 55;
                $scheduled_at->second = 0;
                $exchange_request->scheduled_at = $scheduled_at;

                // 保存だけする
                $exchange_request->save();
            } catch (\Exception $e) {
                $this->info('Carbon parse error:'.$scheduled);
            }
        }

        //
        $this->info('success');
        
        return 0;
    }
}
