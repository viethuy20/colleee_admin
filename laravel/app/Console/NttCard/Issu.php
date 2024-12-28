<?php
namespace App\Console\NttCard;

use Carbon\Carbon;
use App\Console\BaseCommand;
use App\ExchangeRequest;
use App\External\NttCard;

/**
 * Description of Issu
 *
 * @author t_moriizumi
 */
class Issu extends BaseCommand {
    protected $tag = 'ntt_card:issu';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ntt_card:issu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issu ntt_card';

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
        while (true) {
            // ギフト券申し込みを1件取得
            $exchange_request = ExchangeRequest::ofNttCardGiftCode()
                ->ofWaiting($exchange_request_id)
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
            // NttCardオブジェクト作成
            $ntt_card = NttCard::getIssu($exchange_request->number, $exchange_request->gift_type, $exchange_request->face_value);
            // オブジェクトを取得できなかった場合
            if (!isset($ntt_card)) {
                // 自動で組戻し
                $exchange_request->rollbackRequest();
                continue;
            }
            // 実行
            $res = $ntt_card->execute();
            // ネットワーク,タイムアウト,タイムスタンプエラーの場合
            if ($res == NttCard::RETRY_RESULT) {
                $request_error = $request_error + 1;
                // 一定回数を超えた場合はバッチを終了
                if ($request_error >= 10) {
                    $this->info('Network is instability.');
                    break;
                }
                continue;
            }
            // 結果保存
            $exchange_request->response = $ntt_card->getBody();
            $exchange_request->request_level = 1;
            $exchange_request->response_code = $ntt_card->getReturnCode();
            $exchange_request->requested_at = Carbon::now();
            // 正常終了の場合
            if ($res == NttCard::SUCCESS_RESULT) {
                $gift_code = $ntt_card->getGiftCode();
                // ギフトコードが存在する場合
                if (isset($gift_code)) {
                    // 承認
                    $exchange_request->approvalRequest();
                    // ギフトコード送信
                    $exchange_request->sendGiftCode();
                    continue;
                }
            }
            // 自動で組戻し
            $exchange_request->requested_at = Carbon::now();
            $exchange_request->rollbackRequest();
        }
        //
        $this->info('success');
        return 0;
    }
}
