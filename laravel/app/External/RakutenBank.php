<?php
namespace App\External;

use GuzzleHttp\Client;

/**
 * 楽天銀行.
 * @author t_moriizumi
 */
class RakutenBank {
    const HTTP_ENCODE = 'SHIFT_JIS';
    const ENCODE = 'UTF-8';

    const STAUS_RESPONSE = 1;
    const TRANSFER_STAUS_RESPONSE = 2;
    const SCHEDULED_AT_RESPONSE = 3;

    private static $RESPONSE_INDEX = [1 => [self::STAUS_RESPONSE => 0, self::SCHEDULED_AT_RESPONSE => 9],
        2 => [self::STAUS_RESPONSE => 0, self::TRANSFER_STAUS_RESPONSE => 1, self::SCHEDULED_AT_RESPONSE => 9]];

    private $type;
    private $params = [];
    private $body = null;
    private $response = [];

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('rakuten_bank.'.$key);
    }

    /**
     * パスワード暗号化.
     * @return string 暗号化済みパスワード
     */
    public static function encript(string $data) : string {
        return bin2hex(openssl_encrypt($data, 'AES-128-ECB', self::getConfig('SECRET')));
    }

    /**
     * パスワード取得.
     * @return string パスワード
     */
    private static function getPassword() : string {
        return openssl_decrypt(hex2bin(self::getConfig('C_LOGIN_PASSWORD_ENCRYPTED')),
                'AES-128-ECB', self::getConfig('SECRET'));
    }

    /**
     * 振込オブジェクト取得.
     * @param string $request_code 申し込み番号
     * @param int $amount 振込金額
     * @param string $bank_code 振込先銀行コード
     * @param string $branch_code 振込先支店コード
     * @param int $subject 振込先預金種目[1:普通預金,2:当座口座,4:貯蓄預金]
     * @param string $account_number 振込先口座番号
     * @param string $name 振込先口座カナ名義
     * @return RakutenBank 楽天銀行オブジェクト
     */
    public static function getTransfer(string $request_code, int $amount,
            string $bank_code, string $branch_code, int $subject,
            string $account_number, string $name) : RakutenBank {

        $rakuten_bank = new self();
        $rakuten_bank->type = 1;
        $rakuten_bank->params['COMMAND'] = 'REAL_AUTO_PAYMENT_START';
        $rakuten_bank->params['CurrentPageID'] = 'REAL_AUTO_PAYMENT';
        $rakuten_bank->params['CUSTOMER_RESERVED'] = $request_code;
        $rakuten_bank->params['AMOUNT'] = $amount;
        $rakuten_bank->params['C_BRANCH_CODE'] = self::getConfig('C_BRANCH_CODE');
        $rakuten_bank->params['C_ACCOUNT_NUMBER'] = self::getConfig('C_ACCOUNT_NUMBER');
        $rakuten_bank->params['TRUSTER_NAME'] = mb_convert_encoding(self::getConfig('TRUSTER_NAME'), self::HTTP_ENCODE, self::ENCODE);
        $rakuten_bank->params['C_LOGIN_PASSWORD'] = self::getPassword();
        $rakuten_bank->params['BANK_CODE'] = $bank_code;
        $rakuten_bank->params['BRANCH_CODE'] = $branch_code;
        $rakuten_bank->params['SUBJECT'] = $subject;
        $rakuten_bank->params['ACCOUNT_NUMBER'] = $account_number;
        $rakuten_bank->params['KANA_NAME'] = mb_convert_encoding($name, self::HTTP_ENCODE, self::ENCODE);

        return $rakuten_bank;
    }

    /**
     * 振込結果照会オブジェクト取得.
     * @param string $request_code 申し込み番号
     * @return RakutenBank 楽天銀行オブジェクト
     */
    public static function getConfirm(string $request_code) : RakutenBank {

        $rakuten_bank = new self();
        $rakuten_bank->type = 2;
        $rakuten_bank->params['COMMAND'] = 'REAL_AUTO_PAYMENT_REFERENCE_START';
        $rakuten_bank->params['CurrentPageID'] = 'REAL_AUTO_PAYMENT_REFERENCE';
        $rakuten_bank->params['CUSTOMER_RESERVED'] = $request_code;
        return $rakuten_bank;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() : bool{
        $client = new Client();

        // URL取得
        $url = self::getConfig('URL');

        // パラメーター作成
        $params = $this->params;
        $params['ID'] = self::getConfig('MERCHANT_ID');

        $options = ['http_errors' => false, 'timeout' => 60, 'form_params' => $params];
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
            //\Log::info('url:'.$url.',params:'.print_r($params, true));
            $response = $client->request('POST', $url, $options);

            // HTTPステータス確認
            if ($response->getStatusCode() != 200) {
                return false;
            }
            $this->body = mb_convert_encoding($response->getBody(), self::ENCODE, self::HTTP_ENCODE);
            // カンマ区切りなので分解して格納
            $this->response = explode(',', $this->body);
        } catch (\Exception $e) {
            \Log::info('RakutenBank:'.$e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 結果取得.
     * @return string 結果
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * 結果取得.
     * @param int $response_code レスポンスコード
     * @return type
     */
    public function getResponse(int $response_code) {
        return $this->response[self::$RESPONSE_INDEX[$this->type][$response_code]] ?? null;
    }
}
