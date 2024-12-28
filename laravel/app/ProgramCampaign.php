<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgramCampaign extends Model
{
    use DBTrait;

    protected $table = 'program_campaigns';

    protected $guarded = ['id'];

    protected $date = ['deleted_at'];
    //
    /**
     * Add extra attribute.
     */
    protected $appends = [];
    public static function getDefault($program_id = 0) : ProgramCampaign
    {
        $campaign = new self();
        $campaign->program_id = $program_id ?? null;
        return $campaign;
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function saveCampaign(int $admin_id) : bool
    {
        // トランザクション処理
        $campaign = $this;
        $res = DB::transaction(function () use ($admin_id, $campaign) {
            $is_create = !isset($campaign->id);

            $is_dirty = $campaign->isDirty();
            if ($is_dirty) {
                // 登録実行
                $campaign->save();
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
