<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * PayPayエラーログ
 */
class PaypayLogs extends Model
{
    use DBTrait;
    
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'paypay_logs';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
}
