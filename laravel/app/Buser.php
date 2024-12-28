<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Buser extends Model
{
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /** 先行ポイント配付. */
    const PRE_REWARD_TYPE = 1;

    /** ブロック状態. */
    const BLOCKED_STATUS = 0;
    /** 非ブロック状態. */
    const UNBlOCKED_STATUS = 1;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    private static function findBuser(int $type, int $user_id)
    {
        return self::where('user_id', '=', $user_id)
            ->where('type', '=', $type)
            ->where('status', '=', self::BLOCKED_STATUS)
            ->first();
    }

    public static function isBlocked(int $type, int $user_id) : bool
    {
        $buser = self::findBuser($type, $user_id);
        return isset($buser->id);
    }

    /**
     * ブロック実行.
     * @param int $type 種類
     * @param int $user_id ユーザーID
     */
    public static function unblock(int $type, int $user_id)
    {
        $buser = self::findBuser($type, $user_id);
        // 既にブロックされていない場合
        if (!isset($buser->id)) {
            return;
        }
        $buser->status = self::UNBlOCKED_STATUS;
        $buser->deleted_at = Carbon::now();
        $buser->save();
    }
}
