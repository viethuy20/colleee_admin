<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * キャッシュバック用のID(hash値)の紐づけ
 */
class ExchangeRequestCashbackKey extends Model
{
    use DBTrait;
    
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'exchange_request_cashback_keys';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
}
