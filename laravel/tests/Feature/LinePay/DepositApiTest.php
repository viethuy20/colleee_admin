<?php

namespace Tests\Feature\LinePay;

use GuzzleHttp\Client;
use Tests\TestCase;

class DepositApiTest extends TestCase
{
    // テスト実行コマンド
    // ./vendor/bin/phpunit tests/Feature/LinePay/DepositApiTest.php

    protected $client;
    protected $headers;
    protected $order_id;

    protected function setUp(): void
    {
        parent::setUp();
        
        $nonce = date_timestamp_get(date_create());
        $this->order_id = 'Ord' . $nonce;

        $requestBody = [
            'referenceNo' => '11040000009_SB',
            'amount' => '1000',
            'currency' => 'JPY',
            'orderId' => $this->order_id
        ];
        
        $requestBody = json_encode($requestBody);
        $authMacText = config('line_pay.CLIENT_SECRET') . '/v1/partner-deposits' . $requestBody . $nonce;

        $signature = base64_encode(hash_hmac('sha256', $authMacText, config('line_pay.CLIENT_SECRET'), true));
        $this->headers = [
            'content-type' => 'application/json',
            'x-line-authorization' => $signature,
            'x-line-authorization-nonce' => $nonce,
            'x-line-channelid' => config('line_pay.CLIENT_ID'),
        ];

        $this->client = new Client([
            'base_uri' => 'https://sandbox-api-pay.line.me/'
        ]);
    }

    /**
     * 正常系
     * @test
     */
    public function testGetPaymentReferenceNo()
    {
        // リクエストボディのサンプルデータを作成
        $requestBody = [
            'referenceNo' => '11040000009_SB',
            'amount' => '1000',
            'currency' => 'JPY',
            'orderId' => $this->order_id
        ];

        $url = 'v1/partner-deposits';

        // POSTリクエストを送信
        $response = $this->client->post($url, [
            'headers' => $this->headers,
            'json' => $requestBody,
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);
        
        // レスポンスのステータスコードを確認
        $this->assertEquals(200, $response->getStatusCode());

        // 送金成功の場合はreturnCodeが0000になる
        $this->assertEquals('0000', $responseData['returnCode']);
    }
}
