<?php
namespace App;

use Carbon\Carbon;
use DB;

trait PartitionTrait
{
    /**
     * パーティション名一覧取得.
     * @param string $db_name DB名
     * @param string $tb_name テーブル名
     * @param string $max_partition_name 最大値のパーティション名
     * @return array パーティション名一覧
     */
    private static function getPartitionNameList(string $db_name, string $tb_name, string $max_partition_name)
    {
        // パーティションリスト取得
        $partition_list = DB::select(
            DB::raw(
                'SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?;'
            ),
            [$db_name, $tb_name]
        );
        
        $partition_name_list = [];
        foreach ($partition_list as $partition) {
            $partition_name = $partition->PARTITION_NAME;
            // 末尾のパーティションなので何もしない
            if ($partition_name == $max_partition_name) {
                continue;
            }
            $partition_name_list[] = $partition_name;
        }
        
        return $partition_name_list;
    }
    
    /**
     * パーティションを作成.
     * @param array $sql_list SQLリスト
     * @param string $tb_name テーブル名
     * @param string $max_partition_name 最大値パーティション名
     */
    private static function createPartition(array $sql_list, string $tb_name, string $max_partition_name)
    {
        // 予約パーティションが存在しないので終了する
        if (empty($sql_list)) {
            return;
        }
        
        //
        $sql_list[] = sprintf(
            "PARTITION %s VALUES LESS THAN (MAXVALUE) COMMENT = '%s'",
            $max_partition_name,
            $max_partition_name
        );
        $sql = sprintf(
            "ALTER TABLE %s REORGANIZE PARTITION %s INTO (%s);",
            $tb_name,
            $max_partition_name,
            implode(",\n", $sql_list)
        );
        \Log::info('sql:'.$sql);
        DB::statement(DB::raw($sql));
    }
    
    /**
     * パーティションを削除.
     * @param array $partition_name_list パーティション名リスト
     * @param string $tb_name テーブル名
     */
    private static function dropPartition(array $partition_name_list, string $tb_name)
    {
        // 削除パーティションが存在しないので終了する
        if (empty($partition_name_list)) {
            return;
        }
        
        // パーティションを削除する
        $sql = sprintf("ALTER TABLE %s DROP PARTITION %s;", $tb_name, implode(',', $partition_name_list));
        \Log::info('sql:'.$sql);
        DB::statement(DB::raw($sql));
    }
    
    /**
     * 月ごとパーティション最適化実行.
     * @param string $db_name DB名
     * @param string $tb_name テーブル名
     * @param int $partition_expired パーティション有効日数
     * @param int $reserved_partition 予約パーティション日数
     * @return boolean
     */
    public static function refreshMonthRange(
        string $db_name,
        string $tb_name,
        int $partition_expired,
        int $reserved_partition
    ) {
        $max_partition_name = 'pmax';
        
        // パーティション名リスト取得
        $partition_name_list = self::getPartitionNameList($db_name, $tb_name, $max_partition_name);
        
        $month = Carbon::today()->startOfMonth();
        
        // 期限切れのパーティション名一覧を作成
        $drop_partition_name_list = [];
        $expired = $month->copy()->subMonths($partition_expired);
        foreach ($partition_name_list as $partition_name) {
            $y = intval(substr($partition_name, 1, 4), 10);
            $m = intval(substr($partition_name, 5, 2), 10);

            $report_date = new Carbon(sprintf("%04d-%02d-01", $y, $m));

            // 有効期限が切れていないので何もしない
            if ($report_date->gte($expired)) {
                continue;
            }
            $drop_partition_name_list[] = $partition_name;
        }
        // パーティション削除
        self::dropPartition($drop_partition_name_list, $tb_name);
        
        
        $sql_list = [];
        // 予約パーティションを追加
        for ($i = 0; $i < $reserved_partition; ++$i) {
            //
            $report_date = $month->copy()->addMonth($i);
            $partition_name = 'p'.$report_date->format('Ym');
            // 存在するので追加しない
            if (in_array($partition_name, $partition_name_list)) {
                continue;
            }
            
            $sql_list[] = sprintf(
                "PARTITION %s VALUES LESS THAN ('%s') COMMENT = '%s'",
                $partition_name,
                $report_date->copy()->addMonths(1)->format('Y-m-d 00:00:00'),
                $report_date->format('Y-m')
            );
            $partition_name_list[] = $partition_name;
        }
        
        // パーティション作成
        self::createPartition($sql_list, $tb_name, $max_partition_name);

        // 成功
        return true;
    }
    
    /**
     * 日ごとパーティション最適化実行.
     * @param string $db_name DB名
     * @param string $tb_name テーブル名
     * @param int $partition_expired パーティション有効日数
     * @param int $reserved_partition 予約パーティション日数
     * @return boolean
     */
    public static function refreshDateRange(
        string $db_name,
        string $tb_name,
        int $partition_expired,
        int $reserved_partition
    ) {
        $max_partition_name = 'pmax';
        
        // パーティション名リスト取得
        $partition_name_list = self::getPartitionNameList($db_name, $tb_name, $max_partition_name);
        
        $today = Carbon::today();
        
        // 期限切れのパーティション名一覧を作成
        $drop_partition_name_list = [];
        $expired = $today->copy()->subDays($partition_expired);
        foreach ($partition_name_list as $partition_name) {
            $y = intval(substr($partition_name, 1, 4), 10);
            $m = intval(substr($partition_name, 5, 2), 10);
            $d = intval(substr($partition_name, 7, 2), 10);

            $report_date = new Carbon(sprintf("%04d-%02d-%02d", $y, $m, $d));

            // 有効期限が切れていないので何もしない
            if ($report_date->gte($expired)) {
                continue;
            }
            $drop_partition_name_list[] = $partition_name;
        }
        // パーティション削除
        self::dropPartition($drop_partition_name_list, $tb_name);
        
        
        $sql_list = [];
        // 予約パーティションを追加
        for ($i = 0; $i < $reserved_partition; ++$i) {
            //
            $report_date = $today->copy()->addDays($i);
            $partition_name = 'p'.$report_date->format('Ymd');
            // 存在するので追加しない
            if (in_array($partition_name, $partition_name_list)) {
                continue;
            }
            
            $sql_list[] = sprintf(
                "PARTITION %s VALUES LESS THAN ('%s') COMMENT = '%s'",
                $partition_name,
                $report_date->copy()->addDays(1)->format('Y-m-d 00:00:00'),
                $report_date->format('Y-m-d')
            );
        }
        
        // パーティション作成
        self::createPartition($sql_list, $tb_name, $max_partition_name);

        // 成功
        return true;
    }
}
