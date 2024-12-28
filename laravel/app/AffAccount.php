<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * アフィリエイトアカウント.
 */
class AffAccount extends Model
{
    use DBTrait;
    
    /** Fancrew. */
    const FANCREW_TYPE = 1;
    
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'aff_accounts';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Add extra attribute.
     */
    protected $appends = [];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function scopeOfType($query, int $type)
    {
        return $query->where('type', '=', $type)
            ->where('status', '=', 0);
    }
}
