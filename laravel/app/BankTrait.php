<?php
namespace App;

use DB;
use App\PartitionTrait;

/**
 * 銀行.
 */
trait BankTrait
{
    use PartitionTrait;
    
    public static function getNextVersion() : int
    {
        return (self::orderBy('version', 'asc')->value('version') ?? 0) + 1;
    }
    
    public static function initImport(int $next_version)
    {
        $instance = new static;
        
        $db_name = config('database.connections.mysql.database');
        //
        $tb_name = $instance->table;
        
        // パーティション名リスト取得
        $partition_name_list = self::getPartitionNameList($db_name, $tb_name, '');
        
        $partition_name = 'p'.$next_version;
        // 次のバージョンのパーティションが既に存在するので終了
        if (in_array($partition_name, $partition_name_list)) {
            return;
        }
        
        //
        $sql = sprintf(
            'ALTER TABLE %s ADD PARTITION (PARTITION %s VALUES IN(%d))',
            $tb_name,
            $partition_name,
            $next_version
        );
        \Log::info('sql:'.$sql);
        DB::statement(DB::raw($sql));
    }
    
    public static function endImport()
    {
        $version = self::orderBy('version', 'desc')->value('version');
        $partition_name = 'p'.$version;
        
        $instance = new static;
        
        $db_name = config('database.connections.mysql.database');
        //
        $tb_name = $instance->table;
        
        // パーティション名リスト取得
        $partition_name_list = self::getPartitionNameList($db_name, $tb_name, '');
        
        foreach ($partition_name_list as $p_partition_name) {
            if ($partition_name == $p_partition_name) {
                continue;
            }
            
            $sql = sprintf("ALTER TABLE %s DROP PARTITION %s;", $tb_name, $p_partition_name);
            \Log::info('sql:'.$sql);
            DB::statement(DB::raw($sql));
        }
    }
    
    /**
     * アルファベットをひらがなに変換.
     */
    private static function refreshHurigana(string $alphabet) : string
    {
        return str_replace(
            ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
                'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '-', ' ', '・'],
            ['えー', 'びー', 'しー', 'でぃー', 'いー', 'えふ', 'じー', 'えいち', 'あい', 'じぇー',
                'けー', 'える', 'えむ', 'えぬ', 'おー', 'ぴー', 'きゅー', 'あーる', 'えす',
                'てぃー', 'ゆー', 'ぶい', 'だぶりゅー', 'えっくす', 'わい', 'ぜっど', 'ー', '', ''],
            strtolower($alphabet)
        );
    }
    
    /**
     * ふりがなに変換.
     * @param string $hurigana 文字列
     */
    public static function convert2Hurigana(string $hurigana) : string
    {
        return self::refreshHurigana(mb_convert_kana($hurigana, 'rnHcV'));
    }
    
    /**
     * 50音ふりがなに変換.
     * @param string $hurigana 文字列
     */
    public static function convert2HuriganaIndex(string $hurigana) : string
    {
        // ひらがなに変換
        $c_hurigana_index = self::refreshHurigana($hurigana);
        // 濁点,半濁点を分離
        $c_hurigana_index = mb_convert_kana(mb_convert_kana($c_hurigana_index, 'kh'), 'rnHc');
        // 濁点,半濁点を取り除いて、小文字ひらがなを大文字ひらがなに変換
        $c_hurigana_index = str_replace(
            ['゛', '゜', 'ぁ', 'ぃ', 'ぅ', 'ぇ', 'ぉ', 'ゃ', 'ゅ', 'ょ', 'っ'],
            ['', '', 'あ', 'い', 'う', 'え', 'お', 'や', 'ゆ', 'よ', 'つ'],
            $c_hurigana_index
        );
        return $c_hurigana_index;
    }
}
