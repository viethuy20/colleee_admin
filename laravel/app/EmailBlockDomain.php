<?php
namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * メールアドレスブロックドメイン情報.
 */
class EmailBlockDomain extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'email_block_domains';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     *
     */
    public static function getDefault()
    {
        $eb_domain = new self();
        $eb_domain->status = 0;
        return $eb_domain;
    }
}
