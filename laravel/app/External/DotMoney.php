<?php
namespace App\External;

use GuzzleHttp\Client;

/**
 * ドットマネー.
 * @author t_moriizumi
 */
class DotMoney {
    private $path = null;
    private $params = null;
    private $error_code = null;
    private $body = null;
    private $response = null;

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('dot_money.'.$key);
    }

    /**
     * 署名取得.
     * @param array $data データ
     * @return string 署名
     */
    private static function getSignature(array $data) : string {
        // 署名作成
        return hash_hmac('sha256', hash('sha256', implode("\n", $data), false), self::getConfig('HashKey'));
    }

    /**
     * 口座番号.
     * @param bool $use_content_auth コンテンツ認証
     * @param string $account_number 口座番号
     * @return string 口座番号.
     */
    private static function getAccountNumber(bool $use_content_auth, string $account_number) : string {
        if (!$use_content_auth) {
            return $account_number;
        }
        return 'exid-'.$account_number;
    }

    /**
     * 認証URL取得.
     * @param string $user_id ユーザーID
     * @param string $user_name ユーザー名
     * @param string|NULL $state コールバック値
     * @param string|NULL $product_id 対象となる商品の識別子
     * @return string 認証URL
     */
    public static function getAuthUrl($state = null, $product_id = null) : string {
        // パラメーターを作成
        $params = ['access_key' => self::getConfig('AccessKey')];
        if (isset($state)) {
            $params['status'] = $state;
        }
        if (isset($state)) {
            $params['product_id'] = $product_id;
        }
        // URLを作成して返す
        return self::getConfig('AuthURL').'/exchange/authorize'.'?'. http_build_query($params);
    }

    /**
     * コンテンツ認証URL取得.
     * @param string $user_id ユーザーID
     * @param string $user_name ユーザー名
     * @param string $state コールバック値
     * @param string $product_id 対象となる商品の識別子
     * @return string コンテンツ認証URL
     */
    public static function getContentAuthUrl(string $user_id, $user_name = null,
            $state = null, $product_id = null) : string {
        // パラメーターを作成
        $access_date = time();

        $params = ['user_id' => $user_id,
            'access_key' => self::getConfig('AccessKey'),
            'access_date' => $access_date];
        if (isset($user_name)) {
            $params['user_name'] = $user_name;
        }
        if (isset($state)) {
            $params['status'] = $state;
        }
        if (isset($state)) {
            $params['product_id'] = $product_id;
        }

        $params['signature'] = self::getSignature([$access_date, $product_id ?? '', $user_id, $user_name ?? '', $state ?? '']);

        // URLを作成して返す
        return self::getConfig('AuthURL').'/exchange/authorize/external'.'?'. http_build_query($params);
    }

    /**
     * コールバックURL取得.
     * @param string $user_id ユーザーID
     * @param string $user_name ユーザー名
     * @param string $state コールバック値
     * @param string $product_id 対象となる商品の識別子
     * @return string コンテンツ認証URL
     */
    public static function getCallbackUrl(string $user_id, $user_name = null,
            $state = null, $product_id = null) : string {
        // パラメーターを作成
        $access_date = time();

        $params = ['user_id' => $user_id,
            'access_key' => self::getConfig('AccessKey'),
            'access_date' => $access_date];
        if (isset($user_name)) {
            $params['user_name'] = $user_name;
        }
        if (isset($state)) {
            $params['status'] = $state;
        }
        if (isset($product_id)) {
            $params['product_id'] = $product_id;
        }

        $params['signature'] = self::getSignature([$access_date, $product_id ?? '', $user_id, $user_name ?? '', $state ?? '']);

        // URLを作成して返す
        return self::getConfig('AuthURL').'?'. http_build_query($params);
    }

    /**
     * 預入オブジェクト取得.
     * @param bool $use_content_auth コンテンツ認証
     * @param string $request_id 申し込み番号
     * @param string $account_number 口座番号
     * @param int $amount 金額
     * @param string|NULL $product_id 対象となる商品の識別子
     * @return DotMoney ドットマネーオブジェクト
     */
    public static function getDeposit(bool $use_content_auth, string $request_id,
            string $account_number, int $amount, $product_id = null) : DotMoney {
        $dot_money = new self();
        $dot_money->path = sprintf("/account/%s/deposit", self::getAccountNumber($use_content_auth, $account_number));
        $dot_money->params = (object) ['request_id' => $request_id, 'amount' => $amount];
        if (isset($product_id)) {
            $dot_money->params->product_id = $product_id;
        }
        return $dot_money;
    }

    /**
     * 口座情報確認オブジェクト取得.
     * @param bool $use_content_auth コンテンツ認証
     * @param string $account_number 口座番号
     * @return DotMoney ドットマネーオブジェクト
     */
    public static function getShow(bool $use_content_auth, string $account_number) : DotMoney {
        $dot_money = new self();
        $dot_money->path = sprintf("/account/%s", self::getAccountNumber($use_content_auth, $account_number));
        return $dot_money;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() : bool {
        $client = new Client();

        // URL取得
        $url = self::getConfig('URL');

        $request_date = time();

        // メソッドとクエリ作成
        if (isset($this->params)) {
            $method = 'POST';
            $query = json_encode($this->params);
        } else {
            $method = 'GET';
            $query = '';
        }

        // 認証キー作成
        $authorization = implode('_', [
            self::getConfig('Version'),
            self::getConfig('AccessKey'),
            $request_date,
            self::getSignature([$request_date, $method, $this->path, '', $query])]);

        $options = [
            'http_errors' => false,
            'headers' => ['Authorization' => $authorization, 'Content-Type' => 'application/json'],
            'timeout' => 60,
            'body' => $query];
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
            $response = $client->request($method, $url.$this->path, $options);

            // HTTPステータス確認
            $status = $response->getStatusCode();
            $this->body = $response->getBody();
            if (isset($this->body) && $this->body != '') {
                $this->response = json_decode($this->body);
            }
            if ($status != 200) {
                if (isset($this->response->code)) {
                    $this->error_code = $this->response->code;
                }
                return false;
            }
        } catch (\Exception $e) {
            \Log::info('DotMoney:'.$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * エラーコード取得.
     * @return string エラーコード
     */
    public function getErrorCode() {
        return $this->error_code;
    }

    /**
     * 結果取得.
     * @return string 結果
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * レスポンス取得.
     */
    public function getResponse() {
        return $this->response;
    }
}
