<?php
namespace App\External;

use Illuminate\Http\UploadedFile;

/**
 * マウントファイルを設置する.
 * @author t_moriizumi
 */
class MountManager
{
    const IMG_TYPE = 1;
    const BACKUP_USER_TYPE = 2;
    const BACKUP_USER_POINT_TYPE = 3;
    const USER_PROVISION_TYPE = 4;
    const REPORT_TYPE = 5;

    private static function getFilePath(int $type, string $file_name) : ?string
    {
        // ディレクトリを取得
        switch ($type) {
            case self::IMG_TYPE:
                $dir_path = config('path.img_mount');
                break;
            case self::BACKUP_USER_TYPE:
                $dir_path = config('path.log_mount').DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.'users';
                break;
            case self::BACKUP_USER_POINT_TYPE:
                $dir_path = config('path.log_mount').DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.'user_points';
                break;
            case self::USER_PROVISION_TYPE:
                $dir_path = config('path.log_mount').DIRECTORY_SEPARATOR.'user_provisions';
                break;
            case self::REPORT_TYPE:
                $dir_path = config('path.log_mount').DIRECTORY_SEPARATOR.'reports';
                break;
            default:
                return null;
        }
        
        // セパレーターを置換
        $p = ('/' == DIRECTORY_SEPARATOR) ? $file_name : str_replace('/', DIRECTORY_SEPARATOR, $file_name);
        return $dir_path.DIRECTORY_SEPARATOR.trim($p, DIRECTORY_SEPARATOR);
    }
    
    /**
     * ファイルを確認.
     * @param int $type 種類
     * @param string $file_name ファイル名.
     * @return bool 存在する場合はtrueを、存在しない場合はfalseを返す
     */
    public static function fileExists(int $type, string $file_name) :bool
    {
        return file_exists(self::getFilePath($type, $file_name));
    }

    /**
     * ファイルをアップロード.
     * @param int $type 種類
     * @param string $file_name ファイル名.
     * @param UploadedFile $file データ
     */
    public static function upload(int $type, string $file_name, UploadedFile $file)
    {
        // ファイルパスを取得
        $file_path = self::getFilePath($type, $file_name);
        // ディレクトリを作成
        $info = pathinfo($file_path);
        if (!file_exists($info['dirname'])) {
            mkdir($info['dirname'], 0755, true);
        }
        // ファイル移動
        $file->move($info['dirname'], $info['basename']);
    }

    /**
     * ファイルをマウント.
     * @param int $type 種類
     * @param string $org_file_path 元ファイルパス.
     */
    public static function mount(int $type, string $org_file_path)
    {
        // ファイル所法を取得
        $org_info = pathinfo($org_file_path);

        // ファイルパスを取得
        $file_path = self::getFilePath($type, $org_info['basename']);
        // ディレクトリを作成
        $info = pathinfo($file_path);
        if (!file_exists($info['dirname'])) {
            mkdir($info['dirname'], 0755, true);
        }
        // ファイル移動
        copy($org_file_path, $file_path);
    }

    /**
     * ZIP圧縮してファイルをマウント.
     * @param int $type 種類
     * @param string $org_file_path 元ファイルパス.
     */
    public static function zipMount(int $type, string $org_file_path)
    {
        // ファイル所法を取得
        $org_info = pathinfo($org_file_path);
        // ディレクトリを取得
        $dir_path = config('path.backup');
        // ディレクトリが存在しない場合は作成
        if (!file_exists($dir_path)) {
            mkdir($dir_path, '755');
        }
        // ZIPファイルパス
        $zip_file_path = $dir_path.DIRECTORY_SEPARATOR.$org_info['basename'].'.zip';
        // ZIP圧縮
        zip([$org_file_path], $zip_file_path);
        // ファイルをマウント
        self::mount($type, $zip_file_path);
        // ファイル削除
        @unlink($zip_file_path);
    }

    /**
     * ファイルを削除.
     * @param int $type 種類
     * @param string $file_name ファイル名.
     */
    public static function remove(int $type, string $file_name)
    {
        // ファイルパスを取得
        $file_path = self::getFilePath($type, $file_name);
        // ファイルが存在しない場合
        if (!file_exists($file_path)) {
            return;
        }
        // ファイル削除
        unlink($file_path);
    }
}
