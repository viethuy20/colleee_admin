<?php

namespace App\External;

use Illuminate\Support\Facades\Cache;

use Carbon\Carbon;
use GuzzleHttp\Client;

/**
 * GreeAdRewardOffer.
 * @author y_oba
 */
class GreeAdsRewardOffer
{
    const ASP_ID = 46;

    public static $PLATFORM_MAP = [0 => 0, 1 => 1, 2 => 2, 3 => 3,];
    
    public static $THANKS_CATEGORY_MAP = [
        1 => 'インストール',
        2 => '無料会員登録',
        3 => '有料会員登録',
        4 => 'キャンペーン・懸賞応募',
        5 => '商品購入',
        6 => 'サンプル請求',
        7 => 'アンケート・モニター登録',
        8 => '資料請求',
        9 => '見積・査定',
        10 => 'クレジットカード申込・発券',
        11 => 'キャッシング申込・成約',
        12 => '口座開設',
        13 => '予約・来店',
        14 => 'その他',
        15 => 'Video視聴',
        16 => '条件達成（チュートリアル完了・ビンゴ達成等）',
    ];

    public static $CAMPAIGN_SUB_CATEGORY_MAP = [
        1 => '総合通販 / 百貨店 - ショッピングモール',
        2 => '総合通販 / 百貨店 - 百貨店・デパート',
        3 => '総合通販 / 百貨店 - セレクト・名産',
        4 => '総合通販 / 百貨店 - 関連サービス・その他',
        5 => '暮らし - ⽣活雑貨',
        6 => '暮らし - 家具・インテリア',
        7 => '暮らし - ⽣活家電',
        8 => '暮らし - 引越し',
        9 => '暮らし - 関連サービス・その他',
        10 => '美容 / コスメ - コスメ',
        11 => '美容 / コスメ - エステ・ネイル',
        12 => '美容 / コスメ - ダイエット',
        13 => '美容 / コスメ - ヘア・ボディケア',
        14 => '美容 / コスメ - 美容家電',
        15 => '美容 / コスメ - 美容⾷品',
        16 => '美容 / コスメ - 関連サービス・その他',
        17 => 'グルメ - レストラン予約',
        18 => 'グルメ - 飲料',
        19 => 'グルメ - ⾷品',
        20 => 'グルメ - お取り寄せ',
        21 => 'グルメ - 関連サービス・その他',
        22 => 'ファッション - レディース',
        23 => 'ファッション - メンズ',
        24 => 'ファッション - キッズ',
        25 => 'ファッション - アクセサリー・⼩物',
        26 => 'ファッション - その他',
        27 => '健康 - 健康⾷品',
        28 => '健康 - 健康グッズ',
        29 => '健康 - ダイエット',
        30 => '健康 - 医療',
        31 => '健康 - 関連サービス・その他',
        32 => '旅⾏ - 国内旅⾏',
        33 => '旅⾏ - 海外旅⾏',
        34 => '旅⾏ - チケット・航空券',
        35 => '旅⾏ - 旅⾏グッズ',
        36 => '旅⾏ - 関連サービス・その他',
        37 => 'エンタメ - ⾳楽',
        38 => 'エンタメ - 動画・映画',
        39 => 'エンタメ - 本・雑誌',
        40 => 'エンタメ - ゲーム',
        41 => 'エンタメ - 関連サービス・その他',
        42 => 'ファミリー - キッズ・ベビー',
        43 => 'ファミリー - マタニティ',
        44 => 'ファミリー - ペット',
        45 => 'ファミリー - その他',
        46 => '教育 / 資格 - 資格取得',
        47 => '教育 / 資格 - 習い事・塾',
        48 => '教育 / 資格 - その他',
        49 => '⾦融 - 投資',
        50 => '⾦融 - 保険',
        51 => '⾦融 - クレジットカード',
        52 => '⾦融 - その他',
        53 => '仕事 / 転職 - 求⼈',
        54 => '仕事 / 転職 - 事務⽤品',
        55 => '仕事 / 転職 - その他',
        56 => 'PC / 周辺機器 - PC・ソフトウェア',
        57 => 'PC / 周辺機器 - 周辺機器',
        58 => 'PC / 周辺機器 - その他',
        59 => 'サービス / 通信 - プロバイダ',
        60 => 'サービス / 通信 - レンタルサーバー・サイト制作',
        61 => 'サービス / 通信 - ポイントサービス・懸賞',
        62 => 'サービス / 通信 - その他',
        63 => '趣味 / スポーツ - スポーツ',
        64 => '趣味 / スポーツ - アウトドア',
        65 => '趣味 / スポーツ - ⾞・バイク',
        66 => '趣味 / スポーツ - 関連サービス・その他',
        67 => 'ギフト - お中元・お歳暮',
        68 => 'ギフト - 花',
        69 => 'ギフト - その他',
        70 => '結婚 / 恋愛 - ブライダル',
        71 => '結婚 / 恋愛 - 関連サービス・その他',
        72 => 'その他 / その他の商品・サービス',
    ];

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key)
    {
        // 読み込む設定を環境によって切り替える
        return config('gree_ads_rewards_offer.' . $key);
    }

    /**
     * 検索.
     * @return Object|null 検索結果
     */
    public static function get()
    {
        $client = new Client();

        $params = [];
        $params['site_id'] = self::getConfig('site_id');
        $params['media_id'] = self::getConfig('media_id');
        $params['request_time'] = time();
        $params['multi_mission'] = 1;

        $siteKey = self::getConfig('site_key');
        $params['digest'] = hash('sha256', $params['site_id'] . ':' . $params['request_time']. ':' . $siteKey);

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
            \Log::info('GreeAdsRewardOffer:' . $url);
            \Log::info('GreeAdsRewardOffer:' . $e->getMessage());
            \Log::info('GreeAdsRewardOffer:' . $e->getTraceAsString());
            return null;
        }
    }
}
