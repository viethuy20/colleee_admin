<?php
namespace App\Console\RakutenBank;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;

use App\External\RakutenBank;

/**
 * Description of Transfer
 *
 * @author t_moriizumi
 */
class Transfer extends BaseCommand {
    protected $tag = 'rakuten_bank:transfer';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rakuten_bank:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer rakuten_bank';
    
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
        
        $exchange_request_id = 0;
        
        while(true) {
            // 銀行振り込み申し込みを1件取得
            $exchange_request = ExchangeRequest::ofBank()
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
            
            // 銀行口座情報取得
            $account = $exchange_request->bank_account;
            
            // 楽天振込オブジェクト作成
            $rakuten_bank = RakutenBank::getTransfer($exchange_request->number, $exchange_request->face_value,
                    $account->bank_code, $account->branch_code, 1, $account->number,
                    $account->last_name_kana . '　'.$account->first_name_kana);
            
            // 実行
            $res = $rakuten_bank->execute();
            
            $response = isset($exchange_request->response) ? json_decode($exchange_request->response) : (
                    (object) ['transfer' => [], 'transfer_total' => 0, 'confirm' => [], 'confirm_total' => 0]);
            
            if (!$res) {
                $response->transfer_total = $response->transfer_total + 1;
                $exchange_request->response = json_encode($response);
                // ネットワークエラーの場合
                if ($response->transfer_total >= 3) {
                    // エラー上限を超えた場合、管理画面で承認,却下作業をさせる
                    $exchange_request->status = 3;
                } else {
                    // 3分後に実行
                    $exchange_request->scheduled_at = Carbon::now()->addMinutes(3);
                }
                
                $exchange_request->save();
                continue;
            }
            
            $response->transfer[] = $rakuten_bank->getBody();
            
            // 振込依頼書理結果コード取得
            $status = $rakuten_bank->getResponse(RakutenBank::STAUS_RESPONSE);
            
            // 残高不足エラー、システムモードエラーの場合はバッチを終了
            if ($status == '05' || $status == '07') {
                $this->info('error code:'.$status);
                return 0;
            }
            
            $exchange_request->request_level = 1;
            $exchange_request->response_code = '0'.$status;
            $exchange_request->response = json_encode($response);
            $exchange_request->requested_at = Carbon::now();
            
            // 正常終了、二重取引エラーの場合は振込結果照会へ
            if ($status == '00' || $status == '04') {
                if ($account->bank_code == '0036') {
                    // 楽天銀行の場合、リアルタイムなので次の実行は早め
                    $scheduled_at = Carbon::now()->copy()->addHours(3);
                } else {
                    // 結果照会予定日時を登録
                    $scheduled_at = Carbon::now()->copy()->addDays(1);
                    $scheduled_at->hour = 11;
                    $scheduled_at->minute = 55;
                    $scheduled_at->second = 0;
                }

                $exchange_request->scheduled_at = $scheduled_at;
                
                // 承認
                $exchange_request->approvalRequest();
                continue;
            }
            
            // 自動で組戻し
            $exchange_request->rollbackRequest();
        }

        //
        $this->info('success');
        
        return 0;
    }
}
