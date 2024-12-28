<?php
$base_data = [
    'prefix' => 'DG',
    'type' => \App\UserPoint::OTHER_POINT_TYPE,
    'status' => [0 => '預入済み', 1 => '組戻し', 2 => '申し込み中']
];
$env = env('APP_ENV');
if ($env == 'local') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        
    ]);
} elseif ($env == 'development') {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        
    ]);
} else {
    return array_merge($base_data, [
        // プロキシ
        'PROXY' => false,
        // SSL証明書
        'SSL_VERIFY' => false,
        
    ]);
}
