<?php
namespace App;

use DB;

trait DBTrait
{
    /**
     * 文字列ロック.
     * @param string $key 文字列
     * @param integer $time 制限時間
     * @return boolean 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public static function lockString(string $key = 'default_lock', int $time = 3) : bool
    {
        // ロックSQL実行
        $data = DB::select("SELECT GET_LOCK(?, ?) AS 'lock';", [$key, $time]);
        // ロック状態を返す
        return isset($data[0]->lock) && !empty($data[0]->lock);
    }
    
    /**
     * 文字列ロック解除.
     * @param string $key 文字列
     * @return boolean 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public static function unlockString(string $key = 'default_lock') : bool
    {
        // ロックSQL実行
        $data = DB::select("SELECT RELEASE_LOCK(?) AS 'lock';", [$key]);
        // ロック状態を返す
        return isset($data[0]->lock) && !empty($data[0]->lock);
    }

    /**
     * ロックして保存.
     * @param string $lock_key ロックキー
     * @param type $save_func 保存式
     * @param type $check_func 検証関数
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public static function saveWithLock(string $lock_key, $save_func, $check_func = null) : bool
    {
        // ロックに失敗した場合
        if (!self::lockString($lock_key)) {
            return false;
        }
        
        $res = true;
        try {
            // トランザクション処理
            DB::transaction(function () use ($save_func, $check_func) {
                // 関数が存在した場合は実行して、関数の戻り値がfalseの場合は終了する
                if (isset($check_func) && !($check_func())) {
                    // ロールバック
                    throw new RollbackException('Rollback');
                }
                $res = $save_func();
                if (!$res) {
                    // ロールバック
                    throw new RollbackException('Rollback');
                }
                // 登録実行
                return true;
            });
        } catch (RollbackException $e) {
            // ロールバック
            $res = false;
        } catch (\Throwable $e) {
            // ロック解除
            self::unlockString($lock_key);
            throw $e;
        }
        
        // ロック解除
        self::unlockString($lock_key);
        return $res;
    }
    
    /**
     * ビット整数を配列に変換.
     * @param int $bit_data ビット整数
     * @return array 配列
     */
    public static function int2Array(int $bit_data) : array
    {
        $data_array = [];
        for ($i = 1; $i <= 32; $i++) {
            if (!($bit_data >> ($i - 1) & 1 == 1)) {
                continue;
            }
            $data_array[$i] = $i;
        }
        return $data_array;
    }
    
    /**
     * 配列をビット整数に変換.
     * @param array $data_array 配列
     * @return int ビット整数
     */
    public static function array2Int(array $data_array) : int
    {
        $bit_data = 0;
        foreach ($data_array as $data) {
            $bit_data += (1 << ($data - 1));
        }
        return $bit_data;
    }
}
