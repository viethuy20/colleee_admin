<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 銀行.
 */
class Bank extends Model
{
    use DBTrait, BankTrait;
        
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'banks';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
   
    public function branches()
    {
        return $this->hasMany(BankBranch::class, 'bank_id', 'id');
    }
    
    public function scopeOfStable($query)
    {
        $version = Bank::orderBy('version', 'asc')->value('version');
        return $query->where('version', '=', $version)
            ->orderBy('hurigana_index', 'asc');
    }
    
    /**
     * 手数料全額取得.
     * @return int手数料全額
     */
    public function getFullChargeAttribute() :int
    {
        $bank_charge = config('bonus.bank')[0];
        return isset($bank_charge[$this->code]) ? $bank_charge[$this->code] : $bank_charge['default'];
    }
    
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
