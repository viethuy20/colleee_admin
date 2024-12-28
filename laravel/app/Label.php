<?php
namespace App;

use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'labels';

     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * ラベル情報を保存.
     */

      /**
     * Add extra attribute.
     */
    protected $appends = [];

    const TYPE_SHOPPING = 1;
    const TYPE_SERVICE = 2;
    const TYPE_EARN_METHOD = 3;
    const TYPE_POPULAR_CRITERIA = 4;
    const TYPE_ENTRY_MAX = 5;


    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('label_list', function (Builder $builder) {
            $builder->orderBy('labels.priority', 'asc')->orderBy('labels.id', 'asc');
        });
    }

    /**
     * タグ一覧取得.
     * @return array タグ一覧
     */
    public function getTagListAttribute() : array
    {
        // 値を持っていた場合
        if (isset($this->appends['tag_list'])) {
            return $this->appends['tag_list'];
        }
        // タグ一覧を取得
        $tag_id_list = explode(',', $this->tag_ids);
        $this->appends['tag_list'] = empty($tag_id_list) ?
            [] : Tag::whereIn('id', $tag_id_list)->pluck('name')->all();
        return $this->appends['tag_list'];
    }

    /**
     * タグ一覧取得.
     * @return string|null タグ一覧
     */
    public function getTagsAttribute() : ?string
    {
        return empty($this->tag_list) ? null : implode(',', $this->tag_list);
    }

    /**
     * タグ一覧登録.
     * @param array タグ一覧
     */
    public function setTagListAttribute(array $tag_list)
    {
        $tag_id_list = empty($tag_list) ? [] : Tag::whereIn('name', $tag_list)->pluck('id')->all();
        $this->tag_ids = empty($tag_id_list) ? null : implode(',', $tag_id_list);
    }

    /**
     * タグ一覧登録.
     * @param string $tags タグ一覧
     */
    public function setTagsAttribute(?string $tags)
    {
        $this->tag_list = isset($tags) ? explode(',', $tags) : [];
    }

    /**
     * 子ラベル取得.
     */
    public function getChildListAttribute()
    {
        return self::where('label_id', '=', $this->id)->get();
    }
}
