<?php
$base_data = [
    'prefix' => 'PD',
    'type' => \App\UserPoint::OTHER_POINT_TYPE,
    'rollback_error' => ['M010255', 'M010095 ', 'M110659', 'M110663'],
    'duplicate_error' => ['M110664'],
    'break_error' => ['M910001', 'M910034', 'M999999'],
    'response_code' => [
        'M010255' => 'Parameter Check Error GMOポイ活からdocomoへ送信したデータの値が「取引先発生年月日」に半年以上過去の日付が指定されたなど許容範囲外のデータがある場合に発生するエラーです。基本的に再実行で直るはずですが、詳細が知りたい場合はdocomo様に問い合わせが必要です。'
        , 'M010095' => 'Parameter Check Error 入力パラメータエラーエラーが発生しています。 メッセージボディ部の項目が欠落している、送信パラメータの形式が変わったけど未対応等の場合等に発生します。NIKKOエンジニアに問い合わせください。'
        , 'M110659' => 'Member Not Exists 指定されたDクラブ会員番号が存在しません。該当のお客様はポイント進呈が行えない旨をご案内ください。'
        , 'M110663' => 'Invalid PointClub Number 指定されたDクラブ会員番号が無効です。退会済など「個人を特定する情報」が無効の場合に発生します。該当のお客様はポイント進呈が行えない旨をご案内ください。'
        , 'M110664' => 'Duplicate Request 重複エラーが発生しております。初回データの送信は正常終了しているので対応は不要です。(2回目の送信データは破棄されました。)'
    ],
    'status' => [0 => '預入済み', 1 => '組戻し', 2 => '申し込み中']
];
$env = env('APP_ENV');
if ($env == 'local') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'GRANT_API_URL' => 'https://api.apl99.spmode.ne.jp/docomo/prod/v1/dpoint/grant',
        'GRANT_API_HOST' => 'api.apl99.spmode.ne.jp',
        'CLIENT_ID' => 'g00_0478_0002_00',
        'CLIENT_SECRET' => 'feTJ9SPDnD5pf8LWEZchCfScVKkGrqN4',
        'CUSTOMER_STORE_CODE' => '0000000000000000000000000'
    ]);
} elseif ($env == 'development') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'GRANT_API_URL' => 'https://api.apl99.spmode.ne.jp/docomo/prod/v1/dpoint/grant',
        'GRANT_API_HOST' => 'api.apl99.spmode.ne.jp',
        'CLIENT_ID' => 'g00_0478_0002_00',
        'CLIENT_SECRET' => 'feTJ9SPDnD5pf8LWEZchCfScVKkGrqN4',
        'CUSTOMER_STORE_CODE' => '0000000000000000000000000'
    ]);
} else {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'GRANT_API_URL' => 'https://api.apl01.spmode.ne.jp/docomo/prod/v1/dpoint/grant',
        'GRANT_API_HOST' => 'api.apl01.spmode.ne.jp',
        'CLIENT_ID' => 'g00_0478_0001_00',
        'CLIENT_SECRET' => 'smCGTc69eTxufEmSAMA6CB63cVyUU4Ez',
        'CUSTOMER_STORE_CODE' => '0000000000000000000000000'
    ]);
}
