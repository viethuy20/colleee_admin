<?php
$base_data = [
    'point' => [
        App\ExchangeRequest::BANK_TYPE => [
            'config' => 'payment_gateway',
            'label' => '金融機関振込',
            'unit' => '円',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
        ],
        App\ExchangeRequest::AMAZON_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'Amazonギフトカード',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'amazon',
            'gift_type' => App\External\NttCard::AMAZON_OEM,
        ],
        App\ExchangeRequest::ITUNES_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'Apple Gift Card',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'itunes',
            'gift_type' => App\External\NttCard::ITUNES_OEM,
        ],
        App\ExchangeRequest::PEX_GIFT_TYPE => [
            'config' => 'voyage',
            'label' => 'PeXポイントギフト',
            'unit' => 'PeXポイント',
            'yen_rate' => 1000,
            'default' => ['yen_rate' => 100,],
            'email' => 'pex',
            'gift_type' => App\External\Voyage::PEX_TYPE,
        ],
        App\ExchangeRequest::DOT_MONEY_POINT_TYPE => [
            'config' => 'dot_money',
            'label' => 'ドットマネー',
            'unit' => 'マネー',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
        ],
        App\ExchangeRequest::NANACO_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'nanacoギフト',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'nanaco',
            'gift_type' => App\External\NttCard::NANACO_OEM,
        ],
        App\ExchangeRequest::EDY_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'EdyギフトID',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'edy',
            'gift_type' => App\External\NttCard::EDY_OEM,
        ],
        App\ExchangeRequest::GOOGLE_PLAY_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'Google Play ギフト',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'google_play',
            'gift_type' => App\External\NttCard::GOOGLE_PLAY_OEM,
        ],
        App\ExchangeRequest::WAON_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'WAONポイントID',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 110,],
            'email' => 'waon',
            'gift_type' => App\External\NttCard::WAON_POINT_OEM,
        ],
        App\ExchangeRequest::D_POINT_TYPE => [
            'config' => 'd_point',
            'label' => 'Dポイント',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'd_point',
            'gift_type' => App\External\NttCard::WAON_POINT_OEM,
        ],
        App\ExchangeRequest::LINE_PAY_TYPE => [
            'config' => 'line_pay',
            'label' => 'LINE Pay',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,]
        ],
        App\ExchangeRequest::PONTA_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'Pontaポイント コード',
            'unit' => 'ポイント',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'ponta',
            'gift_type' => App\External\NttCard::PONTA_OEM,
        ],
        App\ExchangeRequest::PSSTICKET_GIFT_TYPE => [
            'config' => 'ntt_card',
            'label' => 'プレイステーション ストアチケット',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'pssticket',
            'gift_type' => App\External\NttCard::PSSTICKET_OEM,
        ],
        App\ExchangeRequest::PAYPAY_TYPE => [
            'config' => 'paypay',
            'label' => 'PayPayポイント',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
        ],

        App\ExchangeRequest::KDOL_TYPE => [
            'config' => 'kdol',
            'label' => 'KDOLポイント',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
        ],

        App\ExchangeRequest::DIGITAL_GIFT_PAYPAL_TYPE => [
            'config' => 'digital_gift',
            'label' => 'PayPalポイント',
            'unit' => '円分',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'digital_gift',
        ],


        App\ExchangeRequest::DIGITAL_GIFT_JAL_MILE_TYPE => [
            'config' => 'digital_gift',
            'label' => 'JALマイル',
            'unit' => 'マイル',
            'yen_rate' => 100,
            'default' => ['yen_rate' => 100,],
            'email' => 'digital_gift',
        ],
    ],
];
$gift_list = [
    App\ExchangeRequest::AMAZON_GIFT_TYPE, App\ExchangeRequest::ITUNES_GIFT_TYPE,
    App\ExchangeRequest::PEX_GIFT_TYPE, App\ExchangeRequest::NANACO_GIFT_TYPE,
    App\ExchangeRequest::EDY_GIFT_TYPE, App\ExchangeRequest::GOOGLE_PLAY_GIFT_TYPE,
    App\ExchangeRequest::WAON_GIFT_TYPE, App\ExchangeRequest::PONTA_GIFT_TYPE,
    App\ExchangeRequest::PSSTICKET_GIFT_TYPE,
];
$gift_code_type_map = [];
foreach ($gift_list as $type) {
    $gift_code_type_map[$type] = $base_data['point'][$type]['label'];
}
$base_data['gift_code_type'] = $gift_code_type_map;
return $base_data;
