<?php
namespace App\External;

use Carbon\Carbon;
use GuzzleHttp\Client;

/**
 * NttCard.
 * @author t_moriizumi
 */
class NttCard implements IGiftCode {
    /** 種類. */
    const AMAZON_OEM = 'AGC';
    const ITUNES_OEM = 'ITG';
    const GOOGLE_PLAY_OEM = 'GPG';
    const WAON_POINT_OEM = 'WPI';
    const EDY_OEM = 'EGI';
    const NANACO_OEM = 'NAN';
    const PONTA_OEM = 'PPC';
    const PSSTICKET_OEM = 'PST';

    /** 結果. */
    const SUCCESS_RESULT = 0;
    const ERROR_RESULT = 1;
    const RETRY_RESULT = 2;

    private $sno = null;
    private $oem = null;
    private $val = null;
    private $body = null;
    private $response = null;

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('ntt_card.'.$key);
    }

    /**
     * 発行オブジェクト取得.
     * @param string $sno 表示依頼通番
     * @param string $oem ギフトコード種類
     * @param int $value 金額
     * @return NttCard
     */
    public static function getIssu(string $sno, string $oem, int $value) : ?NttCard {
        $ntt_card = new self();
        $ntt_card->sno = $sno;
        $ntt_card->oem = $oem;
        $ntt_card->val = $value;
        return $ntt_card;
    }

    /**
     * HTTP実行
     * @param string $path パス
     * @param array $params パラメーター
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    private function executeHttp(array $params) : bool {
        $this->body = null;
        $this->response = null;
        $client = new Client();
        // URL取得
        $url = self::getConfig('URL');
        $pParams = $params;
        $data = $pParams['CCD'].$pParams['SNO'].$pParams['OEM'].$pParams['VAL'].$pParams['TMS'];
        $pParams['SIG'] = base64_encode(hash_hmac('sha256', $data, self::getConfig('hash_key'), true));
        $options = [
            'http_errors' => false,
            'timeout' => 120,
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
            if ($code < 200 || $code > 299) {
                return false;
            }
            $this->setBody($response->getBody());
        } catch (\Exception $e) {
            \Log::info('NttCard:'.$e->getMessage());
            \Log::info('NttCard:'.$e->getTraceAsString());
            return false;
        }
        return true;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() : int {
        // 発行
        $issu_params = ['CCD' => self::getConfig('CCD'),
            'SNO' => $this->sno,
            'OEM' => $this->oem,
            'VAL' => $this->val,
            'TMS' => Carbon::now()->format('YmdHis')];
        if (!$this->executeHttp($issu_params)) {
            // ネットワークエラーで発行に失敗した場合、異常終了
            return self::RETRY_RESULT;
        }
        // 正常終了
        $code = $this->getReturnCode();
        if ($code == '0000' || $code == '0001') {
            return self::SUCCESS_RESULT;
        }
        \Log::error('NttCart\execute code:'.$code);
        return self::ERROR_RESULT;
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
        $ntt_card = new self();
        $ntt_card->setBody($body);
        return $ntt_card;
    }

    private function getColunm(int $i) :?string {
        return (isset($this->response[$i]) && $this->response[$i] != '') ? $this->response[$i] : null;
    }

    /**
     * @see IGiftCode::getGiftCode
     */
    public function getGiftCode() :?string {
        return $this->getColunm(1);
    }

    /**
     * @see IGiftCode::getGiftCode2
     */
    public function getGiftCode2() :?string {
        return $this->getColunm(6);
    }

    /**
     * @see IGiftCode::getManagementCode
     */
    public function getManagementCode() :?string {
        return $this->getColunm(2);
    }

    /**
     * @see IGiftCode::getFaceValue
     */
    public function getFaceValue() :?int {
        $value = $this->getColunm(3);
        return (isset($value)) ? intval($value, 10) : null;
    }

    /**
     * @see IGiftCode::getExpireAt
     */
    public function getExpireAt() :?Carbon {
        $value = $this->getColunm(5);
        return (isset($value)) ? Carbon::createFromFormat('Ymd', $value)->endOfDay() : null;
    }

    /**
    * 結果登録.
    * @param string $body 結果
     */
    public function setBody(string $body) {
        $this->body = $body;
        $this->response = explode(',', trim($this->body, "\r\n"));
    }

    /**
     * リターンコード取得.
     * @return string リターンコード
     */
    public function getReturnCode() {
        return $this->getColunm(0);
    }
}
