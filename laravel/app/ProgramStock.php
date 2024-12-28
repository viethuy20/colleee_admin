<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\ProgramStockBatchLog;

class ProgramStock extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'program_stocks';

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

    public function programStockBatchLog()
    {
        return $this->hasOne(ProgramStockBatchLog::class, 'program_stock_id', 'id');
    }

    /**
     * 特別プログラム情報初期値取得.
     * @return ProgramStock 特別プログラム情報
     */
    public static function getDefault($program_id, $stock, $note) : ProgramStock
    {
        $program_stock = new self();
        $program_stock->program_id = $program_id;
        $program_stock->stock_cv = $stock;
        $program_stock->note = $note;
        $program_stock->created_at = Carbon::now();
        $program_stock->updated_at = Carbon::now();
        return $program_stock;
    }

    public static function downStockCV($program_id)
    {
        $programStock = static::where('program_id', $program_id)->get()->last();
        $programStock->stock_cv = ($programStock->stock_cv - 1);
        $programStock->updated_at = Carbon::now();
        return $programStock->save();
    }

    public static function upStockCV($program_id)
    {
        $programStock = static::where('program_id', $program_id)->get()->last();
        $programStock->stock_cv = ($programStock->stock_cv + 1);
        $programStock->updated_at = Carbon::now();
        return $programStock->save();
    }
}
