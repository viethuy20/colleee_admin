<?php
$var_dir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'var';
$upload_dir = $var_dir.DIRECTORY_SEPARATOR.'upload';
$mount_dir = env('MOUNT_PATH');
$env = env('APP_ENV');
if ($env == 'local') {
    return [
    'artisan' => dirname(__DIR__).DIRECTORY_SEPARATOR.'artisan',
    'batch_log' => $var_dir.
        DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'batch'.
        DIRECTORY_SEPARATOR.date('Ymd', time()).'.log',
    'upload' => $upload_dir,
    'bank' => $upload_dir.DIRECTORY_SEPARATOR.'bank',
    'message' => $upload_dir.DIRECTORY_SEPARATOR.'message',
    'reward' => $upload_dir.DIRECTORY_SEPARATOR.'reward',
    'user_point' => $upload_dir.DIRECTORY_SEPARATOR.'user_point',
    'cuenote' => $upload_dir.DIRECTORY_SEPARATOR.'cuenote',
    'logrecoai' => $upload_dir.DIRECTORY_SEPARATOR.'logrecoai',
    'backup' => $var_dir.DIRECTORY_SEPARATOR.'backup',
    'img_mount' => $mount_dir.DIRECTORY_SEPARATOR.'static-colleee'.DIRECTORY_SEPARATOR.'s3images',
    'log_mount' => $mount_dir.DIRECTORY_SEPARATOR.'logs-colleee',
    'php_bin' => (env('APP_ENV') == 'production' || env('APP_ENV') == 'development') ? DIRECTORY_SEPARATOR.'usr'.
        DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'env php' : 'php',
    ];
} elseif ($env == 'development') {
    return [
    'artisan' => dirname(__DIR__).DIRECTORY_SEPARATOR.'artisan',
    'batch_log' => $var_dir.
        DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'batch'.
        DIRECTORY_SEPARATOR.date('Ymd', time()).'.log',
    'upload' => $upload_dir,
    'bank' => $upload_dir.DIRECTORY_SEPARATOR.'bank',
    'message' => $upload_dir.DIRECTORY_SEPARATOR.'message',
    'reward' => $upload_dir.DIRECTORY_SEPARATOR.'reward',
    'user_point' => $upload_dir.DIRECTORY_SEPARATOR.'user_point',
    'cuenote' => $upload_dir.DIRECTORY_SEPARATOR.'cuenote',
    'logrecoai' => $upload_dir.DIRECTORY_SEPARATOR.'logrecoai',
    'backup' => $var_dir.DIRECTORY_SEPARATOR.'backup',
    'img_mount' => $mount_dir.DIRECTORY_SEPARATOR.'dev-static-colleee'.DIRECTORY_SEPARATOR.'s3images',
    'log_mount' => $mount_dir.DIRECTORY_SEPARATOR.'dev-logs-colleee',
    'php_bin' => (env('APP_ENV') == 'production' || env('APP_ENV') == 'development') ? DIRECTORY_SEPARATOR.'usr'.
        DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'env php' : 'php',
    ];
} else {
    return [
    'artisan' => dirname(__DIR__).DIRECTORY_SEPARATOR.'artisan',
    'batch_log' => $var_dir.
        DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'batch'.
        DIRECTORY_SEPARATOR.date('Ymd', time()).'.log',
    'upload' => $upload_dir,
    'bank' => $upload_dir.DIRECTORY_SEPARATOR.'bank',
    'message' => $upload_dir.DIRECTORY_SEPARATOR.'message',
    'reward' => $upload_dir.DIRECTORY_SEPARATOR.'reward',
    'user_point' => $upload_dir.DIRECTORY_SEPARATOR.'user_point',
    'cuenote' => $upload_dir.DIRECTORY_SEPARATOR.'cuenote',
    'logrecoai' => $upload_dir.DIRECTORY_SEPARATOR.'logrecoai',
    'backup' => $var_dir.DIRECTORY_SEPARATOR.'backup',
    'img_mount' => $mount_dir.DIRECTORY_SEPARATOR.'static-colleee'.DIRECTORY_SEPARATOR.'s3images',
    'log_mount' => $mount_dir.DIRECTORY_SEPARATOR.'logs-colleee',
    'php_bin' => (env('APP_ENV') == 'production' || env('APP_ENV') == 'development') ? DIRECTORY_SEPARATOR.'usr'.
        DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'env php' : 'php',
    ];
}

