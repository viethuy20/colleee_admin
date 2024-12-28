<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgramQuestion extends Model
{
    use DBTrait;

    protected $table = 'program_questions';

    protected $guarded = ['id'];

    protected $date = ['deleted_at'];
    //
    /**
     * Add extra attribute.
     */
    protected $appends = [];

    public static function getDefault($program_id = 0) : ProgramQuestion
    {
        $question = new self();
        $question->program_id = $program_id ?? null;
        return $question;
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function saveQuestion(int $admin_id) : bool
    {
        // トランザクション処理
        $question = $this;
        $res = DB::transaction(function () use ($admin_id, $question) {
            $is_create = !isset($question->id);

            $is_dirty = $question->isDirty();
            if ($is_dirty) {
                // 登録実行
                $question->save();
            }
            // 更新作業の場合はここで終了
            if (!$is_create) {
                return true;
            }

            return true;
        });

        return $res;
    }
}
