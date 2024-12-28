<?php
namespace App\External;

use Carbon\Carbon;
use GuzzleHttp\Client;

/**
 * Voyage.
 * @author t_moriizumi
 */
class Voyage implements IGiftCode {
    const AMAZON_TYPE = 1;
    const ITUNES_TYPE = 2;
    const PEX_TYPE = 3;

    private static $IDENTIFY_CODE_MAP = [
        self::AMAZON_TYPE => [
            'amazon100' => 100,
            'amazon300' => 300,
            'amazon500' => 500,
            'amazon1000' => 1000,
            'amazon2000' => 2000,
            'amazon5000' => 5000,
        ],
        self::ITUNES_TYPE => [
            'itunes500' => 500,
            'itunes1000' => 1000,
            'itunes2000' => 2000,
        ],
        self::PEX_TYPE => [
            'pex1000' => 1000,
            'pex3000' => 3000,
            'pex5000' => 5000,
            'pex7500' => 7500,
            'pex10000' => 10000,
            'pex15000' => 15000,
            'pex20000' => 20000,
            'pex30000' => 30000,
            'pex50000' => 50000,
            'pex100000' => 100000,
            'pex200000' => 200000,
        ],
    ];

    private $trade_id = null;
    private $gift_identify_code = null;
    private $body = null;
    private $response = null;

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('voyage.'.$key);
    }

    /**
     * 発行オブジェクト取得.
     * @param string $request_code 申し込み番号
     * @param int $gift_type ギフトコード種類
     * @param int $value 金額
     * @return Voyage|null
     */
    public static function getIssu(string $request_code, int $gift_type, int $value) :?Voyage {
        $gift_identify_code = array_search($value, self::$IDENTIFY_CODE_MAP[$gift_type]);
        // 種類がみつからなかった場合
        if (!isset($gift_identify_code)) {
            return null;
        }

        $voyage = new self();
        $voyage->trade_id = $request_code;
        $voyage->gift_identify_code = $gift_identify_code;
        return $voyage;
    }

    /**
     * HTTP実行
     * @param string $path パス
     * @param array $params パラメーター
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    private function executeHttp(string $path, array $params) : bool {
        $this->body = null;
        $this->response = null;

        $client = new Client();

        // URL取得
        $url = self::getConfig('URL').$path;

        $pParams = $params;
        ksort($pParams);
        $pParams['signature'] = hash_hmac('sha1', rawurlencode(http_build_query($pParams)),
                self::getConfig('hash_key'));

        $options = [
            'http_errors' => false,
            'timeout' => 60,
            'form_params' => $pParams];
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
            $response = $client->request('POST', $url, $options);

            // HTTPステータス確認
            $code = $response->getStatusCode();
            if ($code != 200) {
                return false;
            }
            $this->setBody($response->getBody());
        } catch (\Exception $e) {
            \Log::info('Voyage:'.$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() : bool {
        // 発行
        $issu_params = ['partner_code' => self::getConfig('partner_code'),
            'response_type' => 'json',
            'gift_identify_code' => $this->gift_identify_code,
            'trade_id' => $this->trade_id,
            'timestamp' => time()];
        if (!$this->executeHttp('', $issu_params)) {
            // ネットワークエラーで発行に失敗した場合、異常終了
            return false;
        }

        // 発行済みではない場合、正常終了
        if ($this->getDetailCode() != '03') {
            return true;
        }

        // 発行済みの場合は発行結果照会を実行
        $transaction_params = ['partner_code' => self::getConfig('partner_code'),
            'response_type' => 'json',
            'trade_id' => $this->trade_id,
            'timestamp' => time()];
        return $this->executeHttp('/transaction', $transaction_params);
    }

    /**
     * @see IGiftCode::getBody
     */
    public function getBody() :string {
        return $this->body;
    }

    /**
     * @see IGiftCode::parse
     */
    public static function parse(string $body) :?IGiftCode {
        $voyage = new self();
        $voyage->setBody($body);
        return $voyage;
    }

    /**
     * @see IGiftCode::getGiftCode
     */
    public function getGiftCode() :?string {
        return $this->response->gift_data->code ?? null;
    }

    /**
     * @see IGiftCode::getGiftCode2
     */
    public function getGiftCode2() :?string {
        return null;
    }

    /**
     * @see IGiftCode::getManagementCode
     */
    public function getManagementCode() :?string {
        return $this->response->gift_data->manage_code ?? null;
    }

    /**
     * @see IGiftCode::getFaceValue
     */
    public function getFaceValue() :?int {
        $gift_identify_code = $this->response->gift_data->gift_identify_code;
        foreach (self::$IDENTIFY_CODE_MAP as $gift_type => $identify_code_map) {
            if (!isset($identify_code_map[$gift_identify_code])) {
                continue;
            }
            return $identify_code_map[$gift_identify_code];
        }
    }

    /**
     * @see IGiftCode::getExpireAt
     */
    public function getExpireAt() :?Carbon {
        return isset($this->response->gift_data->expire_date) ? Carbon::createFromFormat('Y-m-d H:i:s', $this->response->gift_data->expire_date) : null;
    }

    /**
    * 結果登録.
    * @param string $body 結果
     */
    public function setBody(string $body) {
        $this->body = $body;
        $this->response = json_decode($this->body);
    }

    /**
     * 詳細コード取得.
     * @return string 詳細コード
     */
    public function getDetailCode() {
        return $this->response->detail_code;
    }

    /**
     * ギフトコード取得.
     * @return type
     */
    public function getGiftData() {
        return $this->response->gift_data;
    }
}
