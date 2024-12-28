<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 欄内容.
 */
class PopupAds extends Model
{
    use SoftDeletes;
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'popup_ads';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function getDefault() : PopupAds
    {
        $ads = new self();
        $now = Carbon::now();
        $ads->priority = 1;
        $ads->start_at = Carbon::create($now->year, $now->month, $now->day, $now->hour, $now->minute, 0);
        $ads->stop_at = Carbon::create('9999', '12', '31', '23', '59', 0);
        return $ads;
    }

}
