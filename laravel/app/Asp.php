<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * ASP.
 */
class Asp extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'asps';
    
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
    
    /** 広告. */
    const PROGRAM_TYPE = 1;
    /** アンケート. */
    const QUESTION_TYPE = 2;
    /** ゲーム. */
    const GAME_TYPE = 3;
    
    /**
     * ASP情報初期値取得.
     * @return Asp ASP
     */
    public static function getDefault()
    {
        $asp = new self();
        $asp->res_allow_ips = '0.0.0.0/0';
        $asp->status = 0;
        return $asp;
    }
}
