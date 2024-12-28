<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * ポイント.
 */
class Point extends Model
{
    use EditLogTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'points';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at', 'sale_stop_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'time_sale' => 'boolean',
        'today_only' => 'boolean',
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'sale_stop_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getEditLogType()
    {
        return EditLog::POINT_TYPE;
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function course()
    {   
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function scopeOfEnable($query)
    {
        $now = Carbon::now();
        return $query->where($this->table.'.status', '=', 0)
            ->where($this->table.'.stop_at', '>=', $now)
            ->where($this->table.'.start_at', '<=', $now);
    }

    public function getEditableAttribute() : bool
    {
        return isset($this->id) ? $this->start_at->gte(Carbon::now()) : true;
    }

    public function getStoppedAttribute() : bool
    {
        return isset($this->id) ? $this->stop_at->lte(Carbon::now()) : false;
    }

    public function getStartAtEditableAttribute() : bool
    {
        // 更新が不可能な場合
        if (!$this->editable || $this->stopped) {
            return false;
        }
        //
        return !$this->start_at_min->eq($this->start_at_max);
    }

    public function getTimeSaleEditableAttribute() : bool
    {
        // 更新が不可能な場合
        if (!$this->editable || $this->stopped) {
            return false;
        }

        $prev_point = $this->previous;
        // 前のポイントが存在しない、または前のポイントがタイムセールの場合
        if (!isset($prev_point) || $prev_point->time_sale) {
            return false;
        }
        return true;
    }

    public function getFeeAttribute() : ?float
    {
        if ($this->fee_type == 1 && isset($this->point)) {
            return floatval($this->point);
        }
        if ($this->fee_type == 2 && isset($this->rate)) {
            return $this->rate * 100;
        }
        return null;
    }

    public function getRewardsAttribute() : ?float
    {
        if ($this->fee_type == 1 && isset($this->reward_amount)) {
            return floatval($this->reward_amount);
        }
        if ($this->fee_type == 2 && isset($this->reward_amount_rate)) {
            return $this->reward_amount_rate * 100;
        }
        return null;
    }

    public function getPreviousAttribute() :?Point
    {
        if (empty($this->program_id)) {
            return null;
        }

        if ($this->program->multi_course == 1 && empty($this->course_id)) {
            return null;
        } 

        $query = self::where('program_id', '=', $this->program_id)
            ->when(!empty($this->course_id), function ($query) {
                return $query->where('course_id', '=', $this->course_id);
            })
            ->where('status', '=', 0)
            ->orderBy('id', 'desc');
        if (isset($this->id)) {
            $query = $query->where('id', '<', $this->id);
        }
        return $query->first();
    }

    public function getNextAttribute() :?Point
    {
        if (empty($this->program_id) || !isset($this->id)) {
            return null;
        }
        return self::where('program_id', '=', $this->program_id)
            ->where('id', '>', $this->id)
            ->when(!empty($this->course_id), function ($query) {
                return $query->where('course_id', '=', $this->course_id);
            })
            ->where('status', '=', 0)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getStartAtMinAttribute() : Carbon
    {
        // 前のポイントを取得
        $prev_point = $this->previous;
        if (!isset($prev_point->id)) {
            // 最初のポイントの場合、開始日時は変更できないので現在の開始日時を返す
            return $this->start_at;
        }

        $now = Carbon::now();
        $timesale_end_at = $now->copy()->addHours(1);
        if (isset($this->id)) {
            if ($now->gte($this->start_at)) {
                // すでに公開している場合、開始日時は変更できないので現在の開始日時を返す
                return $this->start_at;
            }
            if ($now->gte($prev_point->start_at) && $prev_point->time_sale &&
                $timesale_end_at->gte($prev_point->stop_at)) {
                // 前のポイントが公開されたタイムセールで1時間以内に終わる場合、延長はできない
                return $this->start_at;
            }
        }

        // タイムセールの場合、1時間後の日時にする
        $start_at = $prev_point->time_sale ? $timesale_end_at : $now;

        // 前のポイントを確認
        $prev_point_start_at = $prev_point->start_at->copy()->addMinutes(1);
        $start_at = $start_at->lt($prev_point_start_at) ? $prev_point_start_at : $start_at;

        return $start_at;
    }

    public function getStartAtMaxAttribute() : Carbon
    {
        // 前のポイントを取得
        $prev_point = $this->previous;
        if (!isset($prev_point->id)) {
            // 最初のポイントの場合、開始日時は変更できないので現在の開始日時を返す
            return $this->start_at;
        }

        if (!isset($this->id)) {
            // まだDBに登録されていない場合、最大値を返す
            return Carbon::parse('9999-12-31')->endOfMonth();
        }

        $now = Carbon::now();
        $timesale_end_at = $now->copy()->addDays(1);
        if ($now->gte($this->start_at)) {
            // すでに公開している場合、開始日時は変更できないので現在の開始日時を返す
            return $this->start_at;
        }

        if ($now->gte($prev_point->start_at) && $prev_point->time_sale &&
            (isset($prev_point->sale_stop_at) || $timesale_end_at->gte($prev_point->stop_at))) {
            // 前のポイントが公開されたタイムセールで既に1回延長した、または1日以内に終わる場合、延長はできない
            return $this->start_at;
        }

        if (isset($this->next->start_at)) {
            // 次のポイントの開始時間より先に開始しなければいけない
            return $this->next->start_at->copy()->subSeconds(1);
        }
        // 最大日時を返す
        return Carbon::parse('9999-12-31')->endOfMonth();
    }

    /**
     * ポイント情報初期値取得.
     * @return Point ポイント
     */
    public static function getDefault($program_id = 0, $course_id = null) : Point
    {
        $point = new self();
        $point->program_id = $program_id ?? 0;
        $point->course_id = $course_id ?? null;
        $point->all_back = 0;
        $point->time_sale = false;
        $point->today_only = false;
        $point->status = 0;
        
        $now = Carbon::now();
        $previous_point = $point->previous;

        if (isset($previous_point->start_at)) {
            $start_at_min = $now->max($previous_point->start_at);
            $start_at_min = $start_at_min->copy()->addMinutes(20);
        } else {
            $start_at_min = $now->copy()->addYears(-1);
        }
        $point->start_at = Carbon::create(
            $start_at_min->year,
            $start_at_min->month,
            $start_at_min->day,
            $start_at_min->hour,
            $start_at_min->minute - ($start_at_min->minute % 10),
            0
        );
        $point->stop_at = Carbon::parse('9999-12-31')->endOfMonth();

        if (isset($previous_point->fee_type)) {
            $point->fee_type = $previous_point->fee_type;
        }

        return $point;
    }

    /**
     * ポイント情報を保存.
     * @param int $admin_id 管理者ID
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function savePointInner(int $admin_id) : bool
    {
        if (!$this->isDirty()) {
            return true;
        }

        $now = Carbon::now();
        $prev_point = $this->previous;
        // 前のポイントの終了日時を設定
        if (isset($prev_point)) {
            $stop_at = $this->start_at->copy()->subSeconds(1);
            // 前のタイムセールが開始済みで既に終了日が変更されていた場合
            if ($now->gte($prev_point->start_at) && $prev_point->time_sale &&
                $prev_point->stop_at->lt($stop_at)) {
                $prev_point->sale_stop_at = $prev_point->stop_at->copy();
            }
            $prev_point->stop_at = $stop_at;
            $prev_point->save();
        }

        $is_create = !isset($this->id);
        $res = $this->save();
        // ログを保存
        $this->saveEditLog($admin_id, $is_create ? '作成しました' : '更新しました');
        return $res;
    }

    /**
     * コースを取得.
     * @return Course コース
     */
    public function getCourseAttribute() : Course
    {
        // 値を持っていた場合
        if (isset($this->appends['course'])) {
            return $this->appends['course'];
        }
        // コースを取得
        $course = isset($this->id) ? $this->belongsTo(Course::class, 'course_id', 'id')->first() : null;
        // 存在しなかった場合
        if (!isset($course)) {
            $course = Course::getDefault($this->program_id);
        }
        $this->appends['course'] = $course;
        return $course;
    }
}
