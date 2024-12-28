<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * タグ.
 */
class Tag extends Model
{
    use DBTrait;
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'tags';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    const EDIT_LOCK_KEY = 'tag_edit';
    
    /**
     * タグ情報初期値取得.
     * @return Tag タグ情報
     */
    public static function getDefault()
    {
        $tag = new self();
        $tag->program_total = 0;
        return $tag;
    }
    
    /**
     * タグ情報を保存.
     */
    public function saveTag()
    {
        // ロックに失敗した場合
        if (!Tag::lockString(Tag::EDIT_LOCK_KEY)) {
            return false;
        }
        
        // 重複検証
        $builder = Tag::where('name', '=', $this->name);
        // IDがあった場合
        if (isset($this->id)) {
            $builder = $builder->where('id', '<>', $this->id);
        }
        
        $cur_tag =  $builder->first();
        // 重複しているので失敗
        if (isset($cur_tag->id)) {
            // ロック解除
            Tag::unlockString(Tag::EDIT_LOCK_KEY);
            return false;
        }
            
        // トランザクション処理
        $tag = $this;
        $res = DB::transaction(function () use ($tag) {
            // 登録実行
            $tag->save();
            return true;
        });
        
        // ロック解除
        Tag::unlockString(Tag::EDIT_LOCK_KEY);
        
        return $res;
    }

    /**
     * プログラムタグ名リストを保存.
     * @param int $program_id プログラムID
     * @param array $tag_name_list タグ名リスト
     */
    public static function saveLabelTagList(int $label_id, array $tag_name_list)
    {
        // 既存の登録済みタグを確認
        $cur_tag_map = Tag::whereIn('name', $tag_name_list)
            ->pluck('id', 'name')
            ->all();

        $tag_id_list = [];
        // 登録されていない場合はタグを登録
        foreach ($tag_name_list as $tag_name) {
            // 登録済みの場合
            if (isset($cur_tag_map[$tag_name])) {
                $tag_id_list[] = $cur_tag_map[$tag_name];
                continue;
            }
        }
        
        // ラベル登録済みタグID取得
        $cur_tag_id_list = TagLabel::where('label_id', '=', $label_id)
            ->where('status', '=', 0)
            ->pluck('tag_id')
            ->all();
        
        // 新規登録タグIDリスト取得
        $new_tag_id_list = [];
        foreach ($tag_id_list as $tag_id) {
            $pos = array_search($tag_id, $cur_tag_id_list);
            // 登録済みの場合
            if (!($pos === false)) {
                // 登録済みタグIDから除去
                unset($cur_tag_id_list[$pos]);
                continue;
            }
            // 新規作成タグID
            $new_tag_id_list[] = $tag_id;
        }
        
        // プログラムタグ削除処理
        if (!empty($cur_tag_id_list)) {
            TagLabel::where('label_id', '=', $label_id)
                ->where('status', '=', 0)
                ->whereIn('tag_id', $cur_tag_id_list)
                ->update(['status' => 1, 'deleted_at' => Carbon::now()]);
        }
        
        // ラベルタグ登録処理
        if (!empty($new_tag_id_list)) {
            foreach ($new_tag_id_list as $tag_id) {
                $tag_label = new TagLabel();
                $tag_label->label_id = $label_id;
                $tag_label->tag_id = $tag_id;
                $tag_label->save();
            }
        }
    }
}
