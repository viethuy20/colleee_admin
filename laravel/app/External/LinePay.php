<?php

namespace App\External;

use Illuminate\Support\Str;
use GuzzleHttp\Client;

/**
 * LINE PAY
 */
class LinePay
{
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
    private static function getConfig(string $key)
    {
        // 読み込む設定を環境によって切り替える
        return config('line_pay.'.$key);
    }

    /**
     * LINEPayの認証ヘッダーを生成する.
     *
     * @return Array
     */
    public function generateLinePayAuthorizationHeader($requestBody)
    {
        // タイムスタンプをより一意にするために、ランダムな文字列をタイムスタンプと組み合わせる。
        $nonce = Str::random(16) . '_' . time();
        $requestBody = json_encode($requestBody);
        $authMacText = config('line_pay.CLIENT_SECRET') . '/v1/partner-deposits' . $requestBody . $nonce;

        $signature = base64_encode(hash_hmac('sha256', $authMacText, config('line_pay.CLIENT_SECRET'), true));
        return [
            'content-type' => 'application/json',
            'x-line-authorization' => $signature,
            'x-line-authorization-nonce' => $nonce,
            'x-line-channelid' => config('line_pay.CLIENT_ID'),
        ];
    }

    /**
     * 送金実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute($requestBody)
    {
        $client = new Client();

        // プロキシ
        if (!self::getConfig('PROXY')) {
            $options['proxy'] = '';
        }

        // SSL証明書回避
        if (!self::getConfig('SSL_VERIFY')) {
            $options['verify'] = false;
        }

        try {
            $headers = $this->generateLinePayAuthorizationHeader($requestBody);
            // リクエスト実行
            $response = $client->request('POST', self::getConfig('GRANT_API_URL'), [
                'headers' => $headers,
                'json' => $requestBody,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $this->body = json_decode($response->getBody(), true);

            if ($responseData['returnCode'] === '0000') {
                // 成功時の処理
                $transactionId = $responseData['info']['transactionId'];
                $transactionDate = $responseData['info']['transactionDate'];

                return [
                    'returnCode' => $responseData['returnCode'],
                    'transactionId' => $transactionId,
                    'transactionDate' => $transactionDate
                ];
            } else {
                // エラー時の処理
                $errorCode = $responseData['returnCode'];
                $errorMessage = $responseData['returnMessage'];
                $this->error_code = $errorCode;
                \Log::error("LINE Pay Error: Code: {$errorCode}, Message: {$errorMessage}");
                throw new \Exception($errorCode);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * エラーコード取得.
     * @return string エラーコード
     */
    public function getErrorCode()
    {
        return $this->error_code;
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
     * リクエスト取得.
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * レスポンス取得.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * ステータスコード取得.
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }
}
