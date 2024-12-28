<?php
$env = env('APP_ENV');
if ($env == 'local' || $env == 'development') {
    return [
        'URL' => 'https://reward-sb.gree.net/api.rest/2/p/get_campaigns.1',
        'site_id' => '24435',
        'media_id' => '1700',
        'site_key' => '92e58bbff2a64d3a838f59692cdf74fb',
    ];
} else {
    return [
        'URL' => 'https://reward.gree.net/api.rest/2/p/get_campaigns.1',
        'site_id' => '36980',
        'media_id' => '2892',
        'site_key' => '715624377684c9a40757dc00640dcecb',
    ];
}