<?php
$base_data = [
    'prefix' => 'PD',
    'type' => \App\UserPoint::OTHER_POINT_TYPE,
    'success' => 'SUCCESS',
    'rollback_error' => [
        'INVALID_REQUEST_PARAMS', //リクエストにより提供された情報に無効なデータが含まれています。
        'OP_OUT_OF_SCOPE', //操作は許可されていません。
        'MISSING_REQUEST_PARAMS', //設定されたパラメータが無効です。
        'UNAUTHORIZED', //有効なapi keyとsecretが提供されていません。
        'OPA_CLIENT_NOT_FOUND', //OPAクライアントが見つかりません。
        'VALIDATION_FAILED_EXCEPTION', //リクエストパラメータの処理で問題が発生したことを意味します
        'FAILURE', //トランザクションが重複しています。
        'INVALID_USER_AUTHORIZATION_ID', //指定したuserAuthorizationId(PayPayのユーザー認可ID)が無効です。
        'EXPIRED_USER_AUTHORIZATION_ID', //ユーザー認証IDの有効期限が切れています。
        'RESOURCE_NOT_FOUND', //キャンペーンが見つかりません。
        'UNAUTHORIZED_ACCESS', //リソースサーバーへの不正アクセスです。
        'TRANSACTION_NOT_FOUND', //トランザクションが存在しません。
        'BALANCE_OUT_OF_LIMIT', //付与対象ユーザーの残高が制限を超過します。
    ],
    'exceeded_limit_error' => [
        'RATE_LIMIT', //リクエスト制限数超過。
    ],
    'check_cashback_details_error' => [
        'NOT_ENOUGH_MONEY',
        'INTERNAL_SERVICE_ERROR',
        'TRANSACTION_NOT_FOUND',
    ],
    'retry_error' => [
        'NOT_ENOUGH_MONEY', //キャッシュバックトランザクションを完了させるのに十分なキャンペーン予算残高がありません。
        'INTERNAL_SERVICE_ERROR', //サービスエラーが発生しました。
        'SERVICE_ERROR', //サービスエラーが発生しました。
        'INTERNAL_SERVER_ERROR', //問題が発生したことを意味します
        'MAINTENANCE_MODE', //メンテナンス中です。
    ],
    'response_code' => [
        'SUCCESS' => '成功',
        'REQUEST_ACCEPTED' => 'リクエストが受け入れられました',
        'INVALID_REQUEST_PARAMS' => 'リクエストにより提供された情報に無効なデータが含まれています。',
        'OP_OUT_OF_SCOPE' => 'The operation is not permitted.',
        'MISSING_REQUEST_PARAMS' => '設定されたパラメータが無効です。',
        'UNAUTHORIZED' => '有効なapi keyとsecretが提供されていません。',
        'OPA_CLIENT_NOT_FOUND' => 'OPAクライアントが見つかりません。',
        'RATE_LIMIT' => 'リクエスト制限数超過。',
        'SERVICE_ERROR' => 'サービスエラーが発生しました。',
        'INTERNAL_SERVER_ERROR' => 'このコードは問題が発生したことを意味しますが、トランザクションが発生したかどうか正確にはわかりません。',
        'MAINTENANCE_MODE' => 'メンテナンス中です。',
    ],
    'status' => [0 => '交換済み', 1 => 'エラー（ポイント返却済み）', 2 => '申し込み中', 3 => '異常', 5 => 'ポイント交換申請中', 6 => '保留'],
];
$env = env('APP_ENV');
if ($env == 'local' || $env == 'testing') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // LINE PAYポイント進呈API
        'paypay_base_uri' => 'https://stg-api.sandbox.paypay.ne.jp',
        'paypay_api_key' => 'a_iTUjgbLUaK_bRW9',
        'paypay_merchant_id' => '692958820189184000',
        'paypay_secret' => 'lejNpFp6s5MohqqmPEQg+4xHmuGu5lGiZAXi1aitL0c=',
        'system_mail' => 'nk-ike-naoko@koukoku.jp',
    ]);
} elseif ($env == 'development') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'paypay_base_uri' => 'https://stg-api.sandbox.paypay.ne.jp',
        'paypay_api_key' => 'a_iTUjgbLUaK_bRW9',
        'paypay_merchant_id' => '692958820189184000',
        'paypay_secret' => 'lejNpFp6s5MohqqmPEQg+4xHmuGu5lGiZAXi1aitL0c=',
        'system_mail' => 'nk-ike-naoko@koukoku.jp',
    ]);
} else {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'paypay_base_uri' => 'https://api.paypay.ne.jp',
        'paypay_api_key' => 'a_WrVf2MrtlX_pVbU',
        'paypay_merchant_id' => '685518093792411648',
        'paypay_secret' => 'opyyn4OAfSa+EpUdIdqTZA8sgYbTWwwLH/7SSC8bc80=',
        'system_mail' => 'colleee@koukoku.jp',
    ]);
}
