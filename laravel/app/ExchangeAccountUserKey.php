<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * キャッシュバック用のID(hash値)の紐づけ
 */
class ExchangeAccountUserKey extends Model
{
    use DBTrait;
    
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'exchange_account_user_keys';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
}
