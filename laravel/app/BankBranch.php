<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 銀行支店.
 */
class BankBranch extends Model
{
    use DBTrait, BankTrait;
        
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'bank_branchs';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    /**
     * ふりがな登録.
     * @param string $hurigana ふりがな.
     */
    public function setHuriganaAttribute($hurigana)
    {
        $this->attributes['hurigana'] = self::convert2Hurigana($hurigana);
        $this->attributes['hurigana_index'] = self::convert2HuriganaIndex($hurigana);
    }
}
