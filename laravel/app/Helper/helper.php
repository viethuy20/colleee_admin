<?php

function exe_command($command, array $params = [])
{
    $exe_command = $command;
    if (!empty($params)) {
        foreach ($params as $param) {
            $exe_command = $exe_command.' '.escapeshellarg($param);
        }
    }
    
    \Log::info("command:".$exe_command);
    
    // ログ出力を追加
    $c = sprintf("%s >> %s", $exe_command, config('path.batch_log'));
    
    // 通知開始
    if (strpos(PHP_OS, 'WIN')!== false) {
        \Log::info('os:win');
        //Windowsの場合はpopen関数で非同期実行
        //「start」コマンドで非同期実行
        $fp = popen('start "" '.$c, 'r');
        pclose($fp);
        return;
    }
    
    //Linuxの場合はexec関数で非同期実行
    //「>」で出力先指定(＊出力先はnullなので出力しない)
    //「&」で非同期実行
    exec($c.' &');
}

function exe_artisan($command, array $params = [])
{
    //\Artisan::queue($command, $params);
    
    // コマンドを作成
    $options = [config('path.artisan'), $command];
    if (!empty($params)) {
        foreach ($params as $param) {
            // 配列の場合
            if (is_array($param)) {
                foreach ($param as $p) {
                    $options[] = $p;
                }
                continue;
            }
            $options[] = $param;
        }
    }

    exe_command(config('path.php_bin'), $options);
}
function email_quote(string $email) : string
{
    $p = strrpos($email, '@');
    $local = substr($email, 0, $p);
    $local = str_replace("\\", "\\\\", $local);
    $local = str_replace("\"", "\\\"", $local);
    $local = str_replace(" ", "\\ ", $local);
    return '"'.$local.'"'.substr($email, $p);
}
function email_unquote(string $email) : string
{
    $p = strrpos($email, '@');
    $local = substr($email, 0, $p);
    $local = trim($local, '"');
    $local = str_replace("\\ ", " ", $local);
    $local = str_replace("\\\"", "\"", $local);
    $local = str_replace("\\\\", "\\", $local);
    return $local.substr($email, $p);
}
function zip($files, string $zip_path, $mode = 0755)
{
    if (file_exists($zip_path)) {
        @unlink($zip_path);
    }
    $zip = new \ZipArchive();
    if ($res = $zip->open($zip_path, \ZipArchive::CREATE) !== true) {
        throw new \Exception("Zip create error. ZipArchive error code : ".(string) $res);
    }
    foreach ($files as $file) {
        if ($zip->addFile($file, basename($file))) {
            continue;
        }
        $zip->close();
        @unlink($zip_path);
        throw new Exception("Zip create error. ZipArchive error file : ".(string) $file);
    }
    $zip->close();
    chmod($zip_path, $mode);
}

function unzip(string $zip_path, string $dir_path) : ?array
{
    if (!file_exists($zip_path)) {
        return null;
    }

    $zip = new \ZipArchive();
    if ($res = $zip->open($zip_path) !== true) {
        throw new \Exception("Zip open error. ZipArchive error code : ".(string) $res);
    }

    $file_list = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $file_list[] = $dir_path.DIRECTORY_SEPARATOR.$zip->getNameIndex($i);
    }
    $zip->extractTo($dir_path);
    $zip->close();
    return $file_list;
}
