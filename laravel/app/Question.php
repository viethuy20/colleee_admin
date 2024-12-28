<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * アンケート.
 */
class Question extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'questions';
    
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at', 'deleted_at'];
    

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 回答リスト取得.
     * @return array 回答リスト
     */
    public function getAnswerAttribute() : array
    {
        return isset($this->answers) ? json_decode($this->answers) : [];
    }

    /**
     * 回答リスト登録.
     * @param array|NULL $answer_list 回答リスト
     */
    public function setAnswerAttribute($answer_list)
    {
        $answers = [];
        foreach ($answer_list as $answer) {
            if (!isset($answer['label'])) {
                break;
            }
            $answers[] = $answer;
        }
        
        $this->answers = empty($answers) ? null : json_encode($answers);
    }
    
    /**
     * アンケート情報初期値取得.
     * @return Question アンケート
     */
    public static function getDefault() : Question
    {
        $question = new self();
        $question->type = 1;
        $question->status = 2;
        return $question;
    }
}
