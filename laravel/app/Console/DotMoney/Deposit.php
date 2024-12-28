<?php
namespace App\Console\DotMoney;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;

use App\External\DotMoney;

/**
 * Description of Deposit
 *
 * @author t_moriizumi
 */
class Deposit extends BaseCommand {
    protected $tag = 'dot_money:deposit';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dot_money:deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deposit dot_money';

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
        $response_map = config('dot_money.response_code');
        $rollback_error_list = config('dot_money.rollback_error');
        $break_error_list = config('dot_money.break_error');
        while (true) {
            // ポイント交換申し込みを1件取得
            $exchange_request = ExchangeRequest::ofDotMoney()
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

            // DotMoneyオブジェクト作成
            $dot_money = DotMoney::getDeposit(true,
                    $exchange_request->number,
                    $user->name,
                    $exchange_request->face_value);

            // 実行
            $res = $dot_money->execute();

            // エラーコード取得
            $error_code = $dot_money->getErrorCode();

            //
            $exchange_request->response_code = $error_code;
            $exchange_request->response = $dot_money->getBody();
            $exchange_request->request_level = 1;
            $exchange_request->requested_at = Carbon::now();

            // 正常終了の場合
            if ($res || $error_code == 'business.request_id_already_exist') {
                $exchange_request->approvalRequest();
                continue;
            }

            // 組戻しエラーの場合
            if (in_array($error_code, $rollback_error_list, true)) {
                // 自動で組戻し
                $exchange_request->rollbackRequest();
                continue;
            }

            // 停止エラーの場合
            if (in_array($error_code, $break_error_list, true)) {
                $this->info('error code:'.$error_code);
                return 0;
            }

            // エラー回数インクリメント
            $request_error = $request_error + 1;

            // 一定回数を超えた場合はバッチを終了
            if ($request_error >= 10) {
                $this->info('System is instability.');
                break;
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
