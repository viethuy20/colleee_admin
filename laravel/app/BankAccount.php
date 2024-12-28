<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 銀行口座.
 */
class BankAccount extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'bank_accounts';
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
    
    /**
     * 銀行取得.
     * @return Bank|NULL 銀行
     */
    public function getBankAttribute() : ?Bank
    {
        // 値を持っていなかった場合
        if (array_key_exists('bank', $this->appends)) {
            return $this->appends['bank'];
        }
        // 銀行取得
        $this->appends['bank'] = Bank::ofStable()
            ->where('code', '=', $this->bank_code)
            ->first();
        return $this->appends['bank'];
    }
    
    /**
     * 銀行支店取得.
     * @return BankBranch|NULL 銀行支店
     */
    public function getBankBranchAttribute() : ?BankBranch
    {
        // 値を持っていなかった場合
        if (array_key_exists('bank_branch', $this->appends)) {
            return $this->appends['bank_branch'];
        }
        
        $bank = $this->getBankAttribute();
        if (isset($bank->id)) {
            // 銀行支店取得
            $this->appends['bank_branch'] = $bank->branches()
                ->where('code', '=', $this->branch_code)
                ->first();
        } else {
            $this->appends['bank_branch'] = null;
        }

        return $this->appends['bank_branch'];
    }
}
