<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * アンケートコメント.
 */
class UserAnswer extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'user_answers';
}
