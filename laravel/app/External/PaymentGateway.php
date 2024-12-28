<?php

namespace App\External;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * PaymentGateway.
 * @author y_oba
 */
class PaymentGateway
{
  const HTTP_ENCODE = 'SHIFT_JIS';
  const ENCODE = 'UTF-8';

  const STAUS_RESPONSE = 1;
  const STAUS_DETAIL_RESPONSE = 2;
  const TRANSFER_STAUS_RESPONSE = 3;
  const SCHEDULED_AT_RESPONSE = 4;
  const API_ERROR = 5;

  private static $RESPONSE_INDEX = [
    1 => [self::STAUS_RESPONSE => 'Result', self::STAUS_DETAIL_RESPONSE => 'Result_Detail', self::API_ERROR => 'ErrInfo'],
    2 => [self::STAUS_RESPONSE => 'Result', self::STAUS_DETAIL_RESPONSE => 'Result_Detail', self::API_ERROR => 'ErrInfo', self::SCHEDULED_AT_RESPONSE => 'Deposit_Date']
  ];

  private $type;
  private $params = [];
  private $body = null;
  private $response = [];

  /**
   * 設定値取得.
   * @param string $key キー
   * @return mixed 設定値
   */
  private static function getConfig(string $key)
  {
    // 読み込む設定を環境によって切り替える
    return config('payment_gateway.' . $key);
  }

  /**
   * 振込オブジェクト取得.
   * @param string $request_code 申し込み番号
   * @param int $amount 振込金額
   * @param string $bank_code 振込先銀行コード
   * @param string $branch_code 振込先支店コード
   * @param int $account_type 口座種別[1:普通預金,2:当座口座,4:貯蓄預金]
   * @param string $account_number 振込先口座番号
   * @param string $name 振込先口座カナ名義
   * @return PaymentGateway PaymentGatewayオブジェクト
   */
  public static function getTransfer(
    string $request_code,
    int $amount,
    string $bank_code,
    string $branch_code,
    int $account_type,
    string $account_number,
    string $name
  ): PaymentGateway {

    $payment_gateway = new self();
    $payment_gateway->type = 1;
    $payment_gateway->params['Shop_ID'] = self::getConfig('SHOP_ID');
    $payment_gateway->params['Shop_Pass'] = self::getConfig('SHOP_PASSWORD');
    $payment_gateway->params['Deposit_ID'] = $request_code;
    $payment_gateway->params['Bank_Code'] = $bank_code;
    $payment_gateway->params['Branch_Code'] = $branch_code;
    $payment_gateway->params['Account_Type'] = $account_type;
    $payment_gateway->params['Account_Number'] = $account_number;
    $payment_gateway->params['Account_Name'] = $name;
    $payment_gateway->params['Branch_Code_Jpbank'] = "";
    $payment_gateway->params['Account_Number_Jpbank'] = "";
    $payment_gateway->params['Amount'] = $amount;
    $payment_gateway->params['Reserv_Flag'] = "";
    return $payment_gateway;
  }

  /**
   * 振込結果照会オブジェクト取得.
   * @param string $request_code 申し込み番号
   * @return PaymentGateway PaymentGatewayオブジェクト
   */
  public static function getConfirm(string $request_code): PaymentGateway
  {

    $payment_gateway = new self();
    $payment_gateway->type = 2;
    $payment_gateway->params['Shop_ID'] = self::getConfig('SHOP_ID');
    $payment_gateway->params['Shop_Pass'] = self::getConfig('SHOP_PASSWORD');
    $payment_gateway->params['Deposit_ID'] = $request_code;
    return $payment_gateway;
  }

  /**
   * 実行.
   * @param string $path API名
   * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
   */
  public function execute(string $path): bool
  {
    $client = new Client();

    // URL取得
    $url = self::getConfig('URL').$path;

    // パラメーター作成
    $params = $this->params;

    $options = ['http_errors' => false, 'timeout' => 60, 'json' => $params, 'debug' => true];
    // プロキシ
    if (!self::getConfig('PROXY')) {
      $options['proxy'] = '';
    }
    // SSL証明書回避
    if (!self::getConfig('SSL_VERIFY')) {
      $options['verify'] = false;
    }

    try {
      // リクエスト実行
      // \Log::info('url:'.$url.',params:'.print_r($params, true));
      $response = $client->request('POST', $url, $options);

      // HTTPステータス確認
      if ($response->getStatusCode() != 200) {
        return false;
      }
      $this->body = $response->getBody();
      $this->response = json_decode($response->getBody()->getContents(), true);
      // \Log::debug($this->response);
    } catch (\Exception $e) {
      \Log::info('PaymentGateway:' . $e->getMessage());
      return false;
    }

    return true;
  }

  /**
   * 結果取得.
   * @return string 結果
   */
  public function getBody()
  {
    return $this->body;
  }

  /**
   * 結果取得.
   * @param int $response_code レスポンスコード
   * @return type
   */
  public function getResponse(int $response_code)
  {
    if ($this->type == 1) {
      return $this->response[self::$RESPONSE_INDEX[1][$response_code]] ?? null;
    } else {
      return $this->response['bank'][self::$RESPONSE_INDEX[2][$response_code]] ?? null;
    }
  }

  /**
   * APIエラー結果取得.
   * @return array||null エラー結果
   */
  public function getAPIErrorResponse()
  {
    if (!is_array($this->response)) return null;

    return array_map(function ($response) {
      return $response[self::$RESPONSE_INDEX[$this->type][self::API_ERROR]] ?? null;
    }, $this->response);
  }

  /**
   * 定期メンテナンス時間判定.
   * @return boolean
   */
  public static function isDailyMaintenaceTime() {
    $today = Carbon::now();
    $ct = function ($h, $m, $s) {
      return Carbon::createFromTime($h, $m, $s);
    };
    return $today->between($ct(23, 50, 0), $ct(23, 59, 59)) || $today->between($ct(0, 0, 0), $ct(0, 10, 59));
  }
}
