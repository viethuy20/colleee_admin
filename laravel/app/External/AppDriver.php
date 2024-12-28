<?php
namespace App\External;

use Illuminate\Support\Facades\Cache;

use Carbon\Carbon;
use GuzzleHttp\Client;
use WrapPhp;

/**
 * AppDriver.
 * @author t_moriizumi
 */
class AppDriver
{
    const CACHE_KEY = 'AppDriver';

    public static $DEVICE_MAP = [1 => 'Universal(Web)', 2 => 'iOS App', 3 => 'Android App',];
    public static $BUDGET_IS_UNLIMITED_MAP = [true => '無限', false => '有限',];
    public static $SUBSCRIPTION_DURATION_MAP = [0 => '買い切り', 30 => '月額'];
    public static $MARKET_MAP = [
        1 => 'GooglePlay', 2 => '独自マーケット', 4 => 'auスマートパス', 7 => 'App Store',
    ];
    public static $DUPLICATION_TYPE_MAP = [0 => '重複カットあり', 1 => 'デイリー成果案件', 2 => 'マンスリー発生成果',];
    public static $REQUISITE_MAP = [
        1 => '有料インストール', 2 => '無料会員登録', 3 => '有料会員登録', 4 => 'キャンペーン・懸賞応募', 5 => '商品購入', 6 => 'サンプル請求',
        7 => 'アンケート・モニター登録', 8 => '資料請求', 9 => '見積・査定', 10 => 'クレジットカード申し込み・発券', 11 => 'キャッシング申込・成約',
        12 => '口座開設', 13 => '予約・来店', 14 => 'その他', 15 => '無料インストール', 16 => 'ポイントバックなし', 17 => 'アプリ起動',
        18 => 'インストール後チュートリアル完了', 19 => 'インストール後ログイン', 20 => 'インストール後会員登録', 21 => 'auID記入後のログイン',
        22 => 'インストール後条件達成',
    ];

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key)
    {
        // 読み込む設定を環境によって切り替える
        return config('app_driver.'.$key);
    }

    /**
     * 検索.
     * @param bool $use_cache キャッシュを利用
     * @return Object|null 検索結果
     */
    public static function search(bool $use_cache = true)
    {
        $default_data = null;

        // キャッシュを取得
        if ($use_cache) {
            $cache_value = Cache::get(self::CACHE_KEY);
            // キャッシュが存在する場合
            if (isset($cache_value)) {
                $default_data = self::parse($cache_value);
                $cache_expire = Carbon::now()->copy()->addHours(-1);
                // キャッシュの更新時間を確認して、キャッシュを返す
                if ($default_data->last_update->gt($cache_expire)) {
                    return $default_data;
                }
            }
        }

        $client = new Client();
        
        $params = [];
        $params['media_id'] = self::getConfig('MEDIA_ID');
        $params['digest'] = hash('sha256', $params['media_id'].':'.self::getConfig('SITE_KEY'));
        $options = ['http_errors' => false, 'timeout' => 120,];

        // URL取得
        $url = self::getConfig('URL').'?'.http_build_query($params);
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
            // HTTPステータス確認
            $code = $response->getStatusCode();
            if ($code != 200) {
                return $default_data;
            }
            $value = (string) $response->getBody();
            $parsed_data = self::parse($value);
            // 解析に失敗した場合
            if (empty($parsed_data->campaign)) {
                return $default_data;
            }
            // キャッシュを保存
            Cache::forever(self::CACHE_KEY, $value);
            return $parsed_data;
        } catch (\Exception $e) {
            \Log::info('AppDriver:'.$url);
            \Log::info('AppDriver:'.$e->getMessage());
            \Log::info('AppDriver:'.$e->getTraceAsString());
            return null;
        }
    }

    private static function parse(string $value)
    {
        // SimpleXMLElementにパース
        $xml = simplexml_load_string($value);
        // campaign要素種取得
        $campaign_total = WrapPhp::count($xml->campaign);
        $campaign_list = [];
        if ($campaign_total > 0) {
            for ($i = 0; $i < $campaign_total; $i++) {
                $campaign_xml = $xml->campaign[$i];
                $campaign = [
                    'id' => (int) $campaign_xml->id, 'name' => (string) $campaign_xml->name,
                    'location' => (string) $campaign_xml->location, 'remark' => (string) $campaign_xml->remark,
                    'start_time' => Carbon::parse((string) $campaign_xml->start_time),
                    'end_time' => Carbon::parse((string) $campaign_xml->end_time),
                    'budget_is_unlimited' => (int) $campaign_xml->budget_is_unlimited,
                    'detail' => (string) $campaign_xml->detail, 'icon' => (string) $campaign_xml->icon,
                    'url' => (string) $campaign_xml->url, 'platform' => (int) $campaign_xml->platform,
                    'market' => (int) $campaign_xml->market,
                    'price' => (int) $campaign_xml->price,
                    'subscription_duration' => (int) $campaign_xml->subscription_duration,
                    'remaining' => (int) $campaign_xml->remaining,
                    'duplication_type' => (int) $campaign_xml->duplication_type,
                ];
                if ($campaign['start_time']->second > 0) {
                    $campaign['start_time']->second = 0;
                    $campaign['start_time'] = $campaign['start_time']->addMinutes(1);
                }
                if ($campaign['end_time']->second < 59) {
                    $campaign['end_time']->second = 59;
                    $campaign['end_time'] = $campaign['end_time']->addMinutes(-1);
                }
                $advertisement_list = [];
                $advertisement_total = WrapPhp::count($campaign_xml->advertisement);
                for ($j = 0; $j < $advertisement_total; $j++) {
                    $advertisement_xml = $campaign_xml->advertisement[$j];
                    $advertisement_list[$j] = (object) [
                        'id' => (int) $advertisement_xml->id, 'name' => (string) $advertisement_xml->name,
                        'requisite' => (int) $advertisement_xml->requisite,
                        'period' => (int) $advertisement_xml->period,
                        'payment' => (int) $advertisement_xml->payment,
                        'point' => (int) $advertisement_xml->point,
                    ];
                }
                $advertisement_total = WrapPhp::count($campaign_xml->advertisement);
                $campaign['advertisement'] = $advertisement_list;
                $campaign_list[$i] = (object) $campaign;
            }
        }

        return (object) ['last_update' => Carbon::parse((string) ($xml->last_update)), 'campaign' => $campaign_list];
    }
}
