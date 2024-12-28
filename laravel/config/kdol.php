<?php
$base_data = [
    'prefix' => 'KD',
    'type' => \App\UserPoint::OTHER_POINT_TYPE,
    'success' => 'SUCCESS',
    'help_url' => '',
    'response_code' => [
        0=>'Success',
        1=>'Invalid Parameter',
        2=>'Invalid hash',
        3=>'Duplicate id',
    ],
    'response_chashback_code' => [
        1=>'ACCEPTED',
        2=>'SUCCESS',
        3=>'FAILURE',
    ],
    'status' => [0 => '交換済み', 1 => 'エラー（ポイント返却済み）', 2 => '申し込み中', 3 => '異常', 5 => 'ポイント交換申請中'],
];
$env = env('APP_ENV');
if ($env == 'local' || $env == 'testing') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // LINE PAYポイント進呈API
        'encrypt_code'=>'2fs5uTxaBpxcEg9Z',
        'base_uri' => 'https://kdol.me/',
        'redirect_url' => 'http://localhost:8080/kdol/account',
        //テスト環境用のAPI URL
        'api_url'=>[
            'proc_get_gmo_nikko'=>'/proc/proc_get_gmo_nikko_test.kdol',
            'proc_get_gmo_nikko_status'=>'/proc/proc_get_gmo_nikko_status_test.kdol',
            'proc_get_gmo_nikko_cashback_point'=>'/proc/proc_get_gmo_nikko_cashback_point_test.kdol',
            'proc_get_gmo_nikko_cashback_ref'=>'/proc/proc_get_gmo_nikko_cashback_ref_test.kdol',
        ],
        //本番用のAPI URL
        // 'api_url'=>[
        //     'proc_get_gmo_nikko'=>'/proc/proc_get_gmo_nikko.kdol',
        //     'proc_get_gmo_nikko_status'=>'/proc/proc_get_gmo_nikko_status.kdol',
        //     'proc_get_gmo_nikko_cashback_point'=>'/proc/proc_get_gmo_nikko_cashback_point.kdol',
        //     'proc_get_gmo_nikko_cashback_ref'=>'/proc/proc_get_gmo_nikko_cashback_ref.kdol',
        // ],
    ]);
} elseif ($env == 'development') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'encrypt_code'=>'2fs5uTxaBpxcEg9Z',
        'base_uri' => 'https://kdol.me/',
        'redirect_url' => 'https://dev02.colleee.net/kdol/account',
        //テスト環境用のAPI URL
        // 'api_url'=>[
        //     'proc_get_gmo_nikko'=>'/proc/proc_get_gmo_nikko_test.kdol',
        //     'proc_get_gmo_nikko_status'=>'/proc/proc_get_gmo_nikko_status_test.kdol',
        //     'proc_get_gmo_nikko_cashback_point'=>'/proc/proc_get_gmo_nikko_cashback_point_test.kdol',
        //     'proc_get_gmo_nikko_cashback_ref'=>'/proc/proc_get_gmo_nikko_cashback_ref_test.kdol',
        // ],
        //本番用のAPI URL
        'api_url'=>[
            'proc_get_gmo_nikko'=>'/proc/proc_get_gmo_nikko.kdol',
            'proc_get_gmo_nikko_status'=>'/proc/proc_get_gmo_nikko_status.kdol',
            'proc_get_gmo_nikko_cashback_point'=>'/proc/proc_get_gmo_nikko_cashback_point.kdol',
            'proc_get_gmo_nikko_cashback_ref'=>'/proc/proc_get_gmo_nikko_cashback_ref.kdol',
        ],
    ]);
} else { //本番
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        // ポイント進呈API
        'encrypt_code'=>'2fs5uTxaBpxcEg9Z',
        'base_uri' => 'https://kdol.me/',
        'redirect_url' => 'https://colleee.net/kdol/account',
        
        //本番用のAPI URL
        'api_url'=>[
            'proc_get_gmo_nikko'=>'/proc/proc_get_gmo_nikko.kdol',
            'proc_get_gmo_nikko_status'=>'/proc/proc_get_gmo_nikko_status.kdol',
            'proc_get_gmo_nikko_cashback_point'=>'/proc/proc_get_gmo_nikko_cashback_point.kdol',
            'proc_get_gmo_nikko_cashback_ref'=>'/proc/proc_get_gmo_nikko_cashback_ref.kdol',
        ],
    ]);
}