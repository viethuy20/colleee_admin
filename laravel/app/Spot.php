<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * 掲載欄.
 */
class Spot extends Model
{
    public const SPOT_FEATURE_CATEGORY = 13;
    
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'spots';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];


    public function contents()
    {
        return $this->hasMany(Content::class, 'spot_id', 'id')
            ->where('status', '=', 0)
            ->where('stop_at', '>=', Carbon::now()->addDays(-30))
            ->orderBy('priority', 'asc')
            ->orderBy('start_at', 'desc');
    }

    public function getDefaultContent()
    {
        $content = new Content();
        $content->devices = $this->default_devices;
        $now = Carbon::now();
        $content->priority = 1;
        $content->spot_id = $this->id;
        $content->start_at = Carbon::create(
            $now->year,
            $now->month,
            $now->day,
            $now->hour,
            $now->minute - ($now->minute % 10) + 10,
            0
        );
        $content->stop_at = Carbon::parse('9999-12-31')->endOfMonth();
        return $content;
    }
}
