<?php
$base_data = [
    'prefix' => 'PD',
    'type' => \App\UserPoint::OTHER_POINT_TYPE,
    'success' => '0000',
    'rollback_error' => ['1101', '1102', '1104', '1105', '1106', '1108', '1124', '1136', '1142', '1162', '1170', '1172', '1178', '1183', '1184', '1198', '1192', '1199', '2101', '2102', '9000'],
    'response_code' => [
        0 => '成功',
        1101 => 'LINE Pay ナンバー（または追加認証）が間違っています。',
        1102 => 'LINE Pay ナンバーに紐づくLINE Payのアカウントが有効ではありません。',
        1104 => 'API認証に失敗しました。',
        1105 => '実行権限がすでに停止、もしくは解約されています。',
        1106 => 'ヘッダー情報エラー',
        1108 => 'ユーザーステータスなどによりLINE Payユーザーに残高付与できませんでした。',
        1124 => '金額情報エラー',
        1136 => '追加認証キー(電話番号)が間違っています。',
        1142 => '付与できる残高が不足しています。',
        1162 => 'LINE Payのアカウントタイプごとに保持可能な残高は上限があり、その上限を超える場合は送金できません。',
        1170 => '送金実施中に、ユーザーが決済や残高のチャージを行い残高が変更されました。',
        1172 => 'このトランザクションレコードは他の注文番号と同じです。',
        1178 => 'この通貨は利用企業ではサポートされていません。',
        1183 => '残高付与額は事前に設定された最小金額より大きくある必要があります。',
        1184 => '残高付与は事前に設定された最大金額より小さくある必要があります。',
        1198 => '同時にリクエスト処理が実行されている場合に、このエラーが発生します。',
        1192 => 'APIのPathが間違っています。',
        1199 => '内部リクエストエラー',
        2101 => 'パラメータエラー',
        2102 => 'JSONデータフォーマットエラー',
        9000 => '内部エラー'
    ],
    'status' => [0 => '預入済み', 1 => '組戻し', 2 => '申し込み中']
];
$env = env('APP_ENV');
if ($env == 'local' || $env == 'testing') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // LINE PAYポイント進呈API
        'GRANT_API_URL' => 'https://sandbox-api-pay.line.me/v1/partner-deposits',
        'GRANT_API_HOST' => 'https://sandbox-api-pay.line.me',
        'CLIENT_ID' => '1660751609',
        'CLIENT_SECRET' => '2dd44fcde7c04fe78dedee722b2d6c2d',
        'CUSTOMER_STORE_CODE' => '0000000000000000000000000'
    ]);
} elseif ($env == 'development') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'GRANT_API_URL' => 'https://sandbox-api-pay.line.me/v1/partner-deposits',
        'GRANT_API_HOST' => 'https://sandbox-api-pay.line.me',
        'CLIENT_ID' => '1660751609',
        'CLIENT_SECRET' => '2dd44fcde7c04fe78dedee722b2d6c2d',
        'CUSTOMER_STORE_CODE' => '0000000000000000000000000'
    ]);
} else {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'GRANT_API_URL' => 'https://api-pay.line.me/v1/partner-deposits',
        'GRANT_API_HOST' => 'https://api-pay.line.me',
        'CLIENT_ID' => '1657771410',
        'CLIENT_SECRET' => '9af8383629a59498813f64215e3a1e60',
        'CUSTOMER_STORE_CODE' => '0000000000000000000000000'
    ]);
}
