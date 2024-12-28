<?php
namespace App\External;

use GuzzleHttp\Client;

/**
 * Description of CuenoteFC
 *
 * @author t_moriizumi
 */
class CuenoteFC {
    private $path = null;
    private $requestHeader = null;
    private $getParams = null;
    private $postParams = null;

    private $status = null;
    private $responseHeader = null;
    private $body = null;
    private $response = null;

    /**
     * アドレス帳ID取得.
     * @param string $adbook_key アドレス帳キー
     * @return string アドレス帳ID
     */
    public static function getAdBookId(string $adbook_key) : string {
        return self::getConfig('adbook_map.'.$adbook_key);
    }

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('cuenote.'.$key);
    }

    public static function import(string $adbook_id, string $duplicate,
            string $header_style, string $update_device, string $file_path) : CuenoteFC {
        $cuenote_fc = new self();
        $cuenote_fc->path = sprintf("/adbook/%s/import", $adbook_id);

        $cuenote_fc->requestHeader = ['X-Duplicate' => $duplicate,
            'X-HeaderStyle' => $header_style,
            'X-UpdateDevice' => $update_device];
        $cuenote_fc->postParams = ['csv' => file_get_contents($file_path)];
        return $cuenote_fc;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() : bool {
        // 運用サーバーではない場合
        if (env('APP_ENV') != 'production') {
            // 成功
            $this->status = 201;
            $this->responseHeader = ['Location' => '/adbook/test/import/test'];
            return true;

            /*
            // 失敗
            $this->status = 400;
            $this->body = json_encode(['001800002', '001800003', '001800004',
                '001800005', '001800006', '001800007', '001800008']);
            $this->response = json_decode($this->body);
            return false;
            */
            /*
            $this->status = 404;
            $this->body = json_encode(['001800001']);
            $this->response = json_decode($this->body);
            return false;
            */
        }

        $client = new Client();

        // URL取得
        $url = self::getConfig('URL').$this->path;

        $options = ['http_errors' => false, 'headers' => $this->requestHeader, 'timeout' => 60];
        // GETパラメーター
        if (!empty($this->getParams)) {
            $options['query'] = http_build_query($this->getParams);
        }
        // メソッドとクエリ作成
        if (!empty($this->postParams)) {
            $method = 'POST';
            $options['form_params'] = $this->postParams;
        } else {
            $method = 'GET';
        }

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

            // HTTPステータス確認
            $this->status = $response->getStatusCode();
            $this->responseHeader = $response->getHeaders();
            $this->body = $response->getBody();
            if (isset($this->body) && $this->body != '') {
                $this->response = json_decode($this->body);
            }
            if ($this->status < 200 || $this->status > 299) {
                return false;
            }
        } catch (\Exception $e) {
            \Log::info('CuenoteFC:'.$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * HTTPステータス取得.
     * @return int|NULL HTTPステータス
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * ヘッダー取得.
     * @return array|NULL ヘッダー
     */
    public function getHeader() {
        return $this->responseHeader;
    }

    /**
     * 結果取得.
     * @return string|NULL 結果
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
