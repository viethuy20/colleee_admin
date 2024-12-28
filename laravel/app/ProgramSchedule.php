<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * プログラム予定.
 */
class ProgramSchedule extends Model
{
    use EditLogTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'program_schedules';

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at'];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
    ];

    public function getEditLogType()
    {
        return EditLog::PROGRAM_SCHEDULE_TYPE;
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function scopeOfEnable($query)
    {
        $now = Carbon::now();
        return $query->where($this->table.'.start_at', '<=', $now)
            ->where($this->table.'.stop_at', '>=', $now);
    }

    /**
     * プログラム予定取得.
     * @param int|NULL $program_id プログラムID
     * @param int|NULL $course_id コースID
     * @return ProgramSchedule プログラム予定
     */
    public static function getDefault($program_id = null, $course_id = null) : ProgramSchedule
    {
        $program_schedule = new self();
        $now = Carbon::now();
        $program_schedule->start_at = Carbon::create(
            $now->year,
            $now->month,
            $now->day,
            $now->hour,
            $now->minute - ($now->minute % 10) + 10,
            0
        );
        $program_schedule->stop_at = Carbon::parse('9999-12-31 23:59:59');

        if (isset($program_id)) {
            $program_schedule->program_id = $program_id;
            $program_schedule->course_id = $course_id;
            $previous = self::where('program_id', '=', $program_id)
                ->when(!is_null($course_id), function ($query) use ($course_id){
                    return $query->where('course_id', '=', $course_id);
                })
                ->orderBy('id', 'desc')
                ->first();
            if (isset($previous->id)) {
                $program_schedule->fill($previous->only(['reward_condition']));
            }
        }
        return $program_schedule;
    }

    /**
     * ProgramScheduleを保存.
     * @param int $admin_id 管理者ID
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function saveProgramScheduleInner(int $admin_id) : bool
    {
        if (!$this->isDirty()) {
            return true;
        }
        $is_create = !isset($this->id);
        $res = $this->save();
        // ログを保存
        $this->saveEditLog($admin_id, $is_create ? '作成しました' : '更新しました');
        return $res;
    }
}
