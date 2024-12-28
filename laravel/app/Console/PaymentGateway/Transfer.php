<?php
namespace App\Console\PaymentGateway;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\ExchangeRequest;

use App\External\PaymentGateway;
use Illuminate\Support\Facades\Log;

/**
 * Description of Transfer
 *
 * @author y_oba
 */
class Transfer extends BaseCommand {
  protected $tag = 'payment_gateway:transfer';
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'payment_gateway:transfer';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Transfer payment_gateway';

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

      // なくなったら終了
      if (!isset($exchange_request->id)) {
        break;
      }
      $exchange_request_id = $exchange_request->id;

      // 組戻しを確認
      if ($exchange_request->checkRollbackUser()) {
        continue;
      }

      // Dailyの定期メンテンスのエラー回避
      if (PaymentGateway::isDailyMaintenaceTime()) {
        $this->info('daily maintenace time');
        break;
      }

      // 銀行口座情報取得
      $account = $exchange_request->bank_account;

      // PaymentGateWayリアルタイム送金オブジェクト作成
      $payment_gateway = PaymentGateway::getTransfer($exchange_request->number, $exchange_request->face_value,
                $account->bank_code, $account->branch_code, 1, $account->number,
                $account->last_name_kana . '　'.$account->first_name_kana);

      // 実行
      $res = $payment_gateway->execute("/RealDepositRegistration.json");

      $response = ((object) ['transfer' => [], 'transfer_total' => 0,
            'confirm' => [], 'confirm_total' => 0]);

      if (!$res) {
        $response->transfer_total = $response->transfer_total + 1;
        $exchange_request->response = json_encode($response);
        // ネットワークエラーの場合
        if ($response->transfer_total >= 3) {
          // エラー上限を超えた場合、管理画面で承認,却下作業をさせる
          $exchange_request->status = ExchangeRequest::ERROR_STATUS;
        } else {
          // 3分後に実行
          $exchange_request->scheduled_at = Carbon::now()->addMinutes(3);
        }
        $exchange_request->save();
        continue;
      }

      // リアルタイム送金指示結果コード取得(パラメータエラーはAPI処理完了とフォーマットが異なるので整形)
      $errors = $payment_gateway->getAPIErrorResponse();
      if (is_array($errors) && array_values($errors) == $errors) {
        foreach ($errors as $error_info) {
          // 残高不足エラー、システムモードエラー、メンテナンス中の場合はバッチを終了
          if ($error_info == 'BA1120072' || $error_info == 'BA1099999' || $error_info == 'BA9000000') {
            $this->info('error code:' . $error_info);
            return 0;
          }
        }

        $response->transfer_total = $response->transfer_total + 1;
        $response->transfer = $errors;
        $exchange_request->response = json_encode($response);
        $exchange_request->response_code = implode(',', $errors);

        // 自動で組戻し
        $exchange_request->rollbackRequest();
        continue;
      }

      $response->transfer_total = $response->transfer_total + 1;
      $response->transfer[] = json_decode($payment_gateway->getBody(), true);

      $status = $payment_gateway->getResponse(PaymentGateway::STAUS_RESPONSE);
      $detail_status = $payment_gateway->getResponse(PaymentGateway::STAUS_DETAIL_RESPONSE);

      $exchange_request->request_level = 1;

      if ($status == '4' && $detail_status != '') {
        $exchange_request->response_code = '1' . $detail_status;
      } else {
        $exchange_request->response_code = '00' . $status;
      }
      $exchange_request->response = json_encode($response);
      $exchange_request->requested_at = Carbon::now();

      // 正常終了の場合は振込結果照会へ
      if ($status == '1') {
        // リアルタイムなので次の実行は早め
        $scheduled_at = Carbon::now()->copy()->addHours(3);

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
