<?php
namespace App;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

/**
 * 更新履歴.
 */
class EditLog extends Model
{
    use DBTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'edit_logs';
    /**
     * createメソッド実行時に、入力を許可するカラムの指定
     * @var array
     */
    protected $fillable = ['type', 'admin_id', 'target_id', 'message'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['created_at'];


    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 更新日時更新停止.
     * @var bool
     */
    public $timestamps = false;

    const PROGRAM_TYPE = 1;
    const POINT_TYPE = 2;
    const PROGRAM_SCHEDULE_TYPE = 3;
    const COURSE_TYPE = 4;
    /**
     * 履歴作成.
     * @param int $type 種類
     * @param int $admin_id 管理者ID
     * @param int $target_id 対象ID
     * @param string $messege メッセージ
     */
    public static function createLog(int $type, int $admin_id, int $target_id, string $message)
    {
        $edit_log = (new self())->forceFill([
            'type' => $type,
            'admin_id' => $admin_id,
            'target_id' => $target_id,
            'message' => $message,
            'created_at' => Carbon::now()
        ]);
        $edit_log->save();
    }
}
