<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * コース.
 */
class Course extends Model
{
    use DBTrait, EditLogTrait;

    protected $guarded = ['id'];

    protected $date = ['deleted_at'];

    /**
     * Add extra attribute.
     */
    protected $appends = [];

    public function getEditLogType()
    {
        return EditLog::COURSE_TYPE;
    }

    public static function getDefault($program_id = 0) : Course
    {   
        $course = new self();
        $course->program_id = $program_id ?? null;
        $course->aff_course_id = null;
        $course->deleted_at = null;
        $course->priority = 1;
        return $course;
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }
    
    public function points()
    {
        return $this->hasMany(Point::class, 'course_id', 'id');
    }
    
    public function schedules()
    {   
        return $this->hasMany(ProgramSchedule::class, 'course_id', 'id');
    }
    
    /**
     * ポイント取得.
     * @return array ポイント
     */
    public function getPointAttribute()
    {
        // 値を持っていた場合
        if (isset($this->appends['point'])) {
            return $this->appends['point'];
        }
        // ポイントを取得
        $point = isset($this->id) ? $this->points()->ofEnable()->get() : collect();
        // 存在しなかった場合
        if ($point->isEmpty()) {
            $point = array($this->default_point);
        }
        $this->appends['point'] = $point;
        return $point;
    }

    /**
     * ポイント登録.
     */
    public function setPoint($point)
    {
        $this->appends['point'] = $point;
    }

    /**
     * ポイント初期値を取得.
     * @return Point ポイント
     */
    public function getDefaultPointAttribute() : Point
    {
        return Point::getDefault($this->program_id, $this->id);
    }

    /**
     * 予定取得.
     * @return ProgramSchedule 予定
     */
    public function getScheduleAttribute()
    {
        // 値を持っていた場合
        if (isset($this->appends['schedule'])) {
            return $this->appends['schedule'];
        }
        // 予定を取得
        $schedule = isset($this->id) ? $this->schedules()->ofEnable()->get() : collect();
        // 存在しなかった場合
        if ($schedule->isEmpty()) {
            $schedule = [ProgramSchedule::getDefault()];
        }
        $this->appends['schedule'] = $schedule;
        return $schedule;
    }

    public function setSchedule($schedules) {
        $this->appends['schedule'] = $schedules;
    }

    /**
     * プログラム予定初期値を取得.
     * @return ProgramSchedule プログラム予定
     */
    public function getDefaultScheduleAttribute() : ProgramSchedule
    {
        return ProgramSchedule::getDefault($this->program_id, $this->course_id);
    }

    /**
     * コース情報を保存.
     * @param int $admin_id 管理者ID
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function saveCourse(int $admin_id) : bool
    {
        // トランザクション処理
        $course = $this;
        $res = DB::transaction(function () use ($admin_id, $course) {
            $is_create = !isset($course->id);

            $is_dirty = $course->isDirty();
            if ($is_dirty) {
                // 登録実行
                $course->save();
            }

            if ($is_dirty) {
                // ログを保存
                $course->saveEditLog($admin_id, $is_create ? '作成しました' : '更新しました');
            }
            // 更新作業の場合はここで終了
            if (!$is_create) {
                return true;
            }

            // ポイント
            if (isset($course->point)) {
                foreach ($course->point as $point) {
                    $point->program_id = $this->program_id;
                    $point->course_id = $this->id;
                    // 開始日時
                    $point->savePointInner($admin_id);
                }
            }
            // 予定
            if (isset($course->schedule)) {
                foreach ($course->schedule as $schedule) {
                    $schedule->program_id = $this->program_id;
                    $schedule->course_id = $this->id;
                    // 開始日時
                    $schedule->start_at = Carbon::now()->copy()->addYears(-1);
                    $schedule->saveProgramScheduleInner($admin_id);
                }
            }
            return true;
        });

        return $res;
    }

    public function changeStatus(int $admin_id, int $status) : bool
    {
        $this->status = $status;
        if ($status == 1) {
            $this->deleted_at = Carbon::now();
        }
        $course = $this;
        // 保存実行
        return DB::transaction(function () use ($admin_id, $course) {
            // 保存
            $course->save();
            // ログを保存
            $course->saveEditLog($admin_id, $course->status == 1 ? '非公開にしました' : '公開にしました');
            return true;
        });
    }
}
