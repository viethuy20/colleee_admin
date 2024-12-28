<?php

namespace App\External;

use App\OfferProgram;
use GuzzleHttp\Client;

/**
 * SkyFlagOffer.
 * @author y_oba
 */
class SkyFlagOffer
{
    const ASP_ID = 35;

    const PLATFORM_ANDROID = 1;
    const PLATFORM_IOS = 2;
    const PLATFORM_PC = 3;
    const PLATFORM_ALL = 4;

    const CONDITION_ALL = 1;
    const CONDITION_TIMESALL = 2;

    public static $PLATFORM_MAP = [
        self::PLATFORM_ANDROID => OfferProgram::PLATFORM_ANDROID, 
        self::PLATFORM_IOS => OfferProgram::PLATFORM_IOS
    ];
    
    public static $THANKS_CATEGORY_MAP = [
        0 => '不明',
        1 => 'インストール後、アプリ起動',
        2 => 'インストール後、条件達成',
        3 => '無料会員登録',
        4 => '有料会員登録',
        5 => '無料お試し登録',
        6 => 'キャンペーン・懸賞応募',
        7 => '商品購入',
        8 => 'アンケート・モニター登録',
        9 => '資料請求',
        10 => '見積・査定',
        11 => 'クレジットカード申込・発券',
        12 => 'キャッシング申込・成約',
        13 => '口座開設',
        14 => '予約・来店',
        15 => '事前登録',
        99 => 'その他'
    ];

    public static $CAMPAIGN_SUB_CATEGORY_MAP = [
        0 => '不明',
        1 => 'その他',
        51 => 'App/ストラテジー',
        52 => 'App/ロールプレイング',
        53 => 'App/MMO',
        54 => 'App/カジュアル',
        55 => 'App/パズル',
        56 => 'App/キュレーション',
        57 => 'App/スポーツ',
        58 => 'App/カード',
        59 => 'App/箱庭',
        60 => 'App/カジノ',
        61 => 'App/シミュレーション',
        62 => 'App/リズム',
        63 => 'App/アクション',
        64 => 'App/LIVE配信',
        65 => 'App/シューティング',
        66 => 'App/EC',
        67 => 'App/書籍',
        68 => 'App/ライフスタイル',
        69 => 'App/ポイント',
        70 => 'App/金融',
        71 => 'App/ギャンブル',
        72 => 'App/VOD',
        73 => 'App/マッチング',
        100 => 'Web/EC',
        101 => 'Web/VOD',
        102 => 'Web/ポイント',
        103 => 'Web/金融',
        104 => 'Web/ギャンブル',
        105 => 'Web/月額',
        106 => 'Web/会員登録',
        107 => 'Web/ゲーム',
        108 => 'Web/書籍',
        109 => 'Web/マッチング'
    ];

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key)
    {
        // 読み込む設定を環境によって切り替える
        return config('sky_flag_offer.' . $key);
    }


    public static function get($platformType)
    {
        $client = new Client();

        $params = [];
        if ($platformType == SkyFlagOffer::PLATFORM_ANDROID) {
            $params['token'] = self::getConfig('token_android');
        } elseif ($platformType == SkyFlagOffer::PLATFORM_IOS) {
            $params['token'] = self::getConfig('token_ios');
        }

        $params['os'] = $platformType;
        $params['condtion'] = self::CONDITION_ALL;

        $options = ['http_errors' => false, 'timeout' => 120,];

        // URL取得
        $url = self::getConfig('URL') . '?' . http_build_query($params);
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
            $response = $client->request('GET', $url, $options);

            if (empty($response)) {
                return null;
            }

            $value = (string) $response->getBody();
            $parsed_data = json_decode($value, true);
            
            return $parsed_data;
        } catch (\Exception $e) {
            \Log::info('SkyFlagOffer:' . $url);
            \Log::info('SkyFlagOffer:' . $e->getMessage());
            \Log::info('SkyFlagOffer:' . $e->getTraceAsString());
            return null;
        }
    }
}
