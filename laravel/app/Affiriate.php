<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * アフィリエイト.
 */
class Affiriate extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'affiriates';

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

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** 広告. */
    const PROGRAM_TYPE = 1;

    /** ColleeeユーザーID置換パラメーター */
    const COLLEEE_USERID_REPLACE = '@@@COLLEEE_USERID@@@';
    /** RID置換パラメーター */
    const COLLEEE_RID_REPLACE = '@@@COLLEEE_RID@@@';

    public function asp()
    {
        return $this->belongsTo(Asp::Class, 'asp_id', 'id');
    }


    public function getParentAttribute()
    {
        if ($this->parent_type == self::PROGRAM_TYPE) {
            return Program::find($this->parent_id);
        }
        return null;
    }

    /**
     * プログラム取得.
     * @return Program|NULL プログラム
     */
    public function getProgramAttribute()
    {
        return $this->parent;
    }

    public function getEditableAttribute() : bool
    {
        return isset($this->id) ? $this->start_at->gte(Carbon::now()) : true;
    }

    public function scopeOfEnable($query)
    {
        $now = Carbon::now();
        return $query->where($this->table.'.status', '=', 0)
            ->where($this->table.'.stop_at', '>=', $now)
            ->where($this->table.'.start_at', '<=', $now);
            //->orderBy($this->table.'.stop_at', 'asc');
    }

    public function getPreviousAttribute()
    {
        $query = self::where('parent_type', '=', $this->parent_type)
            ->where('parent_id', '=', $this->parent_id)
            ->where('status', '=', 0)
            ->orderBy('id', 'desc');
        if (isset($this->id)) {
            $query = $query->where('id', '<', $this->id);
        }
        return $query->first();
    }

    public function getNextAttribute()
    {
        if (!isset($this->id)) {
            return null;
        }
        return self::where('parent_type', '=', $this->parent_type)
            ->where('parent_id', '=', $this->parent_id)
            ->where('id', '>', $this->id)
            ->where('status', '=', 0)
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * 一時改修.
     */
    public function getAcceptDaysAttribute()
    {
        if (!isset($this->attributes['accept_days'])) {
            return null;
        }
        $converted_value = 0;
        if ($this->attributes['accept_days'] >= 31) {
            $converted_value = 31;
        } elseif ($this->attributes['accept_days'] >= 13) {
            $converted_value = 13;
        } elseif ($this->attributes['accept_days'] >= 8) {
            $converted_value = 8;
        } elseif ($this->attributes['accept_days'] >= 4) {
            $converted_value = 4;
        } elseif ($this->attributes['accept_days'] >= 2) {
            $converted_value = 2;
        } elseif ($this->attributes['accept_days'] >= 1) {
            $converted_value = 1;
        }

        return $converted_value;
    }

    /**
     * アフィリエイト情報初期値取得.
     * @return Affiriate アフィリエイト情報
     */
    public static function getDefault() : Affiriate
    {
        $affiriate = new Affiriate();
        $affiriate->give_days = 0;
        $affiriate->status = 0;
        $now = Carbon::now();
        $affiriate->start_at = Carbon::create(
            $now->year,
            $now->month,
            $now->day,
            $now->hour,
            $now->minute - ($now->minute % 10) + 10,
            0
        );
        $affiriate->stop_at = Carbon::parse('9999-12-31 23:59:59');
        return $affiriate;
    }
}
