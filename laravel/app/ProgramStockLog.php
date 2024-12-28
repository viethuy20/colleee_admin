<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProgramStockLog extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'program_stock_logs';

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    /**
     * ラベル情報を保存.
     */
    /**
     * Add extra attribute.
     */
    protected $appends = [];

    /**
     * Disable timestamps.
     */
    public $timestamps = false;

    /**
     * Create a new program stock logs instance after run batch program:up_down_stock
     *
     * @param  $start $end $result
     * @return \App\ProgramStockLog
     */
    public static function createLog($start, $end, $result): ProgramStockLog
    {
        return ProgramStockLog::create([
            'start' => $start,
            'end' => $end,
            'result' => $result,
            'created_at' => $end
        ]);
    }
}
