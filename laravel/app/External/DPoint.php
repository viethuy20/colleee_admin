<?php
namespace App\External;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * Dポイント.
 * @author t_moriizumi
 */
class DPoint {
    private $params = null;
    private $status_code = null;
    private $error_code = null;
    private $body = null;
    private $request = null;
    private $response = null;

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('d_point.'.$key);
    }

    /**
     * 預入オブジェクト取得.
     * @param bool $d_pt_number dポイントクラブ会員番号
     * @param string $customer_date 取引先営業日
     * @param int $amount 金額
     * @return DPoint Dポイントオブジェクト
     */
    public static function getGrant(string $d_pt_number,
            string $customer_date, $request_id, $point) : DPoint {
        $d_point = new self();
        $d_point->params = (object) [
            'api_identity_common' => (object) [
                'd_pt_number' => $d_pt_number
            ],
            'customer_date' => date('Y-m-d', strtotime($customer_date)),        //取引先営業日
            'customer_occur_date' => date('Y-m-d', strtotime($customer_date)),  //取引先発生年月日
            "customer_occur_time" => date("H:i:s", strtotime($customer_date)),  //取引先発生時刻
            "channel_division" => '02',                                         //チャネル区分
            "customer_store_code" => self::getConfig('CUSTOMER_STORE_CODE'),    //取引先店舗コード
            "customer_number" => sprintf('%08d', $request_id),
        ];

        $point_infos = (object) [
            'point_kind' => '1',
            'point_color_division' => '02'
        ];

        $d_point->params->transaction_point = [
            0 => (object) [
                'point_info' => $point_infos,
                'point_number' => $point
            ]
        ];
        return $d_point;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() {

        //ドコモテスト用　これを付けるとリクエストが見えるらしい。

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create();
        $stack->push($history);

        $client = new Client(['handler' => $stack]);
        //ここまで　ドコモテスト用

        //$client = new Client();
        // URL取得
        $url = self::getConfig('GRANT_API_URL');

        // メソッドとクエリ作成
        $method = 'POST';
        $options = [
            'http_errors' => false,
            'headers' => [
                'Host' => self::getConfig('GRANT_API_HOST'),
                'x-ibm-client-id' => self::getConfig('CLIENT_ID'),
                'x-ibm-client-secret' => self::getConfig('CLIENT_SECRET'),
                'Content-Type' => 'application/json; charset=UTF8',
                'Content-Length' => strlen(json_encode($this->params)),
            ],
            'timeout' => 62,
            'json' => $this->params,
            'version' => '1.1',
            'debug'  =>  true
        ];

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
            $response = $client->request($method, $url, $options);

            foreach ($container as $transaction) {
                $this->request = $transaction['request']->getBody();
            }

            // HTTPステータス確認
            $status = $response->getStatusCode();
            $this->status_code = $status;
            $this->body = $response->getBody();

            if (isset($this->body) && $this->body != '') {
                $this->response = json_decode($this->body);
            }
            if ($status == 200) {
                if (isset($this->response->result) && $this->response->result == 'NG' ) {
                    $this->error_code = $this->response->guidance_code;
                    return false;
                } else {
                    return true;
                }
            } else {
                $this->status_code = $status;
                return false;
            }
        } catch (\Exception $e) {
            \Log::info('DPoint:'.$e->getMessage());
            return false;
        }
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
     * リクエスト取得.
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * レスポンス取得.
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * ステータスコード取得.
     */
    public function getStatusCode() {
        return $this->status_code;
    }
}
