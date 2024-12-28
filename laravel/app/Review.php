<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * プログラム.
 */
class Review extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'reviews';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['pointed_at'];

    protected $casts = [
        'pointed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function scopeOfEnable($query, int $program_id)
    {
        return $query->where('program_id', '=', $program_id)
            ->where('status', '=', 0);
    }

    /**
     * ユーザー名を取得.
     * @return string ユーザー名
     */
    public function getUserNameAttribute()
    {
        return User::getNameById($this->user_id);
    }
    
    /**
     * 口コミを保存.
     */
    private function saveInner()
    {
        // 更新
        $this->save();

        // プログラムの評価も更新
        Program::where('id', '=', $this->program_id)
            ->update([
                'review_total' => Review::ofEnable($this->program_id)->count(),
                'review_avg' => round(Review::ofEnable($this->program_id)->avg('assessment'), 1)
            ]);
    }
    
    /**
     * 状態を更新.
     * @param int $status 0:承認,1:却下
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function changeStatus(int $status) : bool
    {
        // 更新データをセット
        $this->status = $status;
        //
        $review = $this;
        
        $user = $this->user;

        // ユーザーが存在しない場合、終了
        if (!$user) {
            return false;
        }
        
        // 承認ではないまたは退会ユーザーの場合、ポイント配付が発生しないので、通常の登録作業
        if ($status != 0 || $user->is_withdrawal) {
            // トランザクション処理
            return DB::transaction(function () use ($review) {
                $review->saveInner();
                return true;
            });
        }

        // 対象のレビュー期間のポイント数を取得
        $set_date   = date('Y-m-d H:i:s');
        $review_point_management = ReviewPointManagement::where('start_at', '<=', $set_date)->where(function ($query) use ($set_date) {
            // stop_atがnullの場合（終了日が設定されていない）もしくは終了日の範囲内
            $query->whereNull('stop_at')
                ->orWhere('stop_at', '>=', $set_date);
        })->first();

        $user_point = UserPoint::getDefault(
            $this->user_id,
            UserPoint::REVIEW_TYPE,
            $review_point_management ? $review_point_management->point : 5,
            0,
            '口コミ'
        );
        $user_point->parent_id = $this->program_id;

        // ポイント配付
        return $user_point->addPoint(null, function () use ($review) {
            // ポイント配付が発生しているかを確認
            $pointed = Review::ofEnable($review->program_id)
                ->where('user_id', '=', $review->user_id)
                ->whereNotNull('pointed_at')
                ->exists();
            // ポイント配付日時が存在しない場合は、ポイント配付日時を登録する
            if (!$pointed) {
                $review->pointed_at = Carbon::now();
            }

            // 更新
            $review->saveInner();
            
            return !$pointed;
        });
    }
}
