<?php
$env = env('APP_ENV');
$base_data = [
    'token_android' => 'FR1ZtTUaSbQnrn1iLuQtUhG1kiMtIXJO',
    'token_ios' => 'z88980xEETQHivQ977qmVODkTBcgN84C',
];
if ($env == 'local' || $env == 'development') {
    return array_merge($base_data, [
        'URL' => 'https://api.stg.skyflag.jp/external/offer/list',
    ]);
} else {
    return array_merge($base_data, [
        'URL' => 'https://api.skyflag.jp/external/offer/list',
    ]);
}