<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
/**
 * 欄内容.
 */
class Entries extends Model
{
    const STATUS_END     = 1; // 終了済み
    const STATUS_START   = 2; // 公開中
    const STATUS_STANDBY = 3; // 公開待ち

    protected $table = 'entries';

    protected $guarded = ['id'];

    //protected $dates = ['start_at', 'stop_at', 'deleted_at'];
    public static function getDefault()
    {
        $entries = new self();
        return $entries;
    }
    public function saveEntries(int $admin_id) : bool
    {
        // トランザクション処理
        $entries = $this;
        $res = DB::transaction(function () use ($admin_id, $entries) {
            $is_create = !isset($entries->id);

            $is_dirty = $entries->isDirty();
            if ($is_dirty) {
                // 登録実行
                $entries->save();
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