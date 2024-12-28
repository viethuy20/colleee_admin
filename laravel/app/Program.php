<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use WrapPhp;

/**
 * プログラム.
 */
class Program extends Model
{
    use DBTrait, EditLogTrait;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'programs';

    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at', 'released_at', 'deleted_at',];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'released_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Add extra attribute.
     */
    protected $appends = [];

    public function getEditLogType()
    {
        return EditLog::PROGRAM_TYPE;
    }

    public function affiriates()
    {
        return $this->hasMany(Affiriate::class, 'parent_id', 'id')
            ->where('parent_type', '=', Affiriate::PROGRAM_TYPE);
    }
    public function points()
    {
        return $this->hasMany(Point::class, 'program_id', 'id');
    }
    // @codingStandardsIgnoreStart
    public function program_tags()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasMany(ProgramTag::class, 'program_id', 'id')
            ->where('status', '=', 0)
            ->orderBy('id', 'asc');
    }
    // @codingStandardsIgnoreStart
    public function program_labels()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasMany(ProgramLabel::class, 'program_id', 'id')
            ->where('status', '=', 0)
            ->orderBy('label_id', 'asc');
    }
    public function schedules()
    {
        return $this->hasMany(ProgramSchedule::class, 'program_id', 'id');
    }
    // @codingStandardsIgnoreStart
    public function credit_card()
    {
        $program = $this;
        // @codingStandardsIgnoreEnd
        return $this->hasOne(CreditCard::class, 'program_id', 'id')
            ->withDefault(function ($credit_card) use ($program) {
                $credit_card->program_id = $program->id;
                $credit_card->brands = 0;
                $credit_card->emoneys = 0;
                $credit_card->insurances = 0;
                $credit_card->status = 0;
                $credit_card->title = $program->title;
                $credit_card->start_at = $program->start_at;
                $credit_card->stop_at = $program->stop_at;
                $affiriate = $program
                    ->affiriates()
                    ->orderBy('id', 'desc')
                    ->first();
                $credit_card->img_url = $affiriate->img_url;
            });
    }
    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'admin_id');
    }

    /**
     * アフィリエイト初期値を取得.
     * @return Affiriate アフィリエイト
     */
    public function getDefaultAffiriateAttribute() : Affiriate
    {
        $affiriate = Affiriate::getDefault();
        $affiriate->parent_type = Affiriate::PROGRAM_TYPE;
        if (isset($this->id)) {
            $affiriate->parent_id = $this->id;
        }
        return $affiriate;
    }

    /**
     * アフィリエイト取得.
     * @return Affiriate アフィリエイト
     */
    public function getAffiriateAttribute() : Affiriate
    {
        // 値を持っていた場合
        if (isset($this->appends['affiriate'])) {
            return $this->appends['affiriate'];
        }
        // アフィリエイトを取得
        $affiriate = isset($this->id) ? $this->affiriates()->ofEnable()->first() : null;
        // 存在しなかった場合
        if (!isset($affiriate->id)) {
            $affiriate = $this->default_affiriate;
        }
        $this->appends['affiriate'] = $affiriate;
        return $affiriate;
    }

    /**
     * アフィリエイト登録.
     * @param Affiriate アフィリエイト
     */
    public function setAffiriateAttribute(Affiriate $affiriate)
    {
        $this->appends['affiriate'] = $affiriate;
    }

    /**
     * コース取得
     * @return array コースリスト
     */
    function getCourseAttribute()
    {
        if (isset($this->appends['course'])) {
            return $this->appends['course'];
        }
        $course = isset($this->id) ? $this->courses()->get() : null;
        if ($course->isEmpty()) {
            $course = array();
        }
        $this->appends['course'] = $course;
        return $course;
    }

    /**
     * コース登録
     * @param array コースリスト
     */
    public function setCourses($course) {
        $this->appends['course'] = $course;
    }

    public function getDefaultCourseAttribute() : Course
    {
        return Course::getDefault($this->id);
    }

    //question
    function getQuestionAttribute()
    {
        if (isset($this->appends['question'])) {
            return $this->appends['question'];
        }
        $question = isset($this->id) ? $this->questions()->get() : null;
        if ($question->isEmpty()) {
            $question = array();
        }
        $this->appends['question'] = $question;
        return $question;
    }

    public function setQuestions($question) {
        $this->appends['question'] = $question;
    }

    public function getDefaultQuestionAttribute() : ProgramQuestion
    {
        return ProgramQuestion::getDefault($this->id);
    }

    public function getDefaultCampaignAttribute() : ProgramCampaign
    {
        return ProgramCampaign::getDefault($this->id);
    }

    /**
     * ポイント初期値を取得.
     * @return Point ポイント
     */
    public function getDefaultPointAttribute() : Point
    {
        return Point::getDefault($this->id);
    }

    /**
     * ポイント取得.
     * @return array ポイント
     */
    public function getPointAttribute()
    {
        // 値を持っていた場合
        if (isset($this->appends['point'])) {
            return $this->appends['point'];
        }
        // ポイントを取得
        $point = isset($this->id) ? $this->points()->ofEnable()->get() : collect();
        // 存在しなかった場合
        if ($point->isEmpty()) {
            $point = array($this->default_point);
        }
        $this->appends['point'] = $point;
        return $point;
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'program_id', 'id');
    }

    public function questions()
    {
        return $this->hasMany(ProgramQuestion::class, 'program_id', 'id');
    }

    public function campaigns()
    {
        return $this->hasMany(ProgramCampaign::class, 'program_id', 'id');
    }

    /**
     * ポイント登録.
     */
    public function setPoints($point)
    {
        $this->appends['point'] = $point;
    }

    /**
     * 予定取得.
     * @return ProgramSchedule 予定
     */
    public function getScheduleAttribute()
    {
        // 値を持っていた場合
        if (isset($this->appends['schedule'])) {
            return $this->appends['schedule'];
        }
        // 予定を取得
        $schedule = isset($this->id) ? $this->schedules()->ofEnable()->get() : collect();
        // 存在しなかった場合
        if ($schedule->isEmpty()) {
            $schedule = [ProgramSchedule::getDefault()];
        }
        $this->appends['schedule'] = $schedule;
        return $schedule;
    }

    /**
     * 予定登録.
     * @param ProgramSchedule $schedule 予定
     */
    public function setScheduleAttribute($schedule)
    {
        $this->appends['schedule'] = $schedule;
    }

    /**
     * 端末取得.
     */
    public function getDeviceAttribute() : array
    {
        return self::int2Array($this->devices);
    }

    /**
     * 端末登録.
     */
    public function setDeviceAttribute($device_list)
    {
        $this->devices = isset($device_list) ? self::array2Int($device_list) : 0;
    }

    /**
     * キャリア取得.
     */
    public function getCarrierAttribute() : array
    {
        return self::int2Array($this->carriers);
    }

    /**
     * キャリア登録.
     */
    public function setCarrierAttribute($carrier_list)
    {
        $this->carriers = isset($carrier_list) ? self::array2Int($carrier_list) : 0;
    }

    /**
     * ショップカテゴリ取得.
     */
    public function getShopCategoryAttribute() : array
    {
        return self::int2Array($this->shop_categories);
    }

    /**
     * ショップカテゴリ登録.
     */
    public function setShopCategoryAttribute($shop_category_list)
    {
        $this->shop_categories = isset($shop_category_list) ? self::array2Int($shop_category_list) : 0;
    }

    /**
     * 保存されたタグID一覧を取得.
     * @return array タグID一覧
     */
    private function getSavedTagIdList() : array
    {
        return $this->program_tags()
            ->pluck('tag_id')
            ->all();
    }

    /**
     * タグID一覧取得.
     * @return array タグID一覧
     */
    public function getTagIdListAttribute() : array
    {
        // 値を持っていた場合
        if (isset($this->appends['tag_id_list'])) {
            return $this->appends['tag_id_list'];
        }

        // タグリストを取得
        $this->appends['tag_id_list'] = isset($this->id) ? $this->getSavedTagIdList() : [];
        return $this->appends['tag_id_list'];
    }

    /**
     * タグID一覧登録.
     * @param array $tag_id_list タグID一覧
     */
    public function setTagIdListAttribute(array $tag_id_list)
    {
        $this->appends['tag_id_list'] = $tag_id_list;
    }

    /**
     * タグ取得.
     * @return string タグ
     */
    public function getTagsAttribute() : string
    {
        return implode(',', $this->tag_list);
    }

    /**
     * タグ登録.
     * @param string|null $tags タグ
     */
    public function setTagsAttribute(?string $tags)
    {
        $this->tag_list = isset($tags) ? explode(',', $tags) : [];
    }

    /**
     * タグ一覧を取得.
     * @return array タグ一覧
     */
    public function getTagListAttribute() : array
    {
        $tag_id_list = $this->tag_id_list;

        $tag_list = [];
        if (!empty($tag_id_list)) {
            // タグ情報を取得する
            $tag_map = Tag::whereIn('id', $tag_id_list)
                ->pluck('name', 'id')
                ->all();

            // 並べ替える
            foreach ($tag_id_list as $tag_id) {
                if (isset($tag_map[$tag_id])) {
                    $tag_list[] = $tag_map[$tag_id];
                }
            }
        }

        $multi_join_tag = array_values(config('map.multi_join_tag'));
        return array_diff($tag_list, $multi_join_tag);
    }

    /**
     * タグ一覧を登録.
     * @param array $tag_list タグ一覧
     */
    public function setTagListAttribute(array $tag_list)
    {
        array_push($tag_list, config('map.multi_join_tag')[$this->multi_join]);

        $tag_id_list = [];
        foreach ($tag_list as $name) {
            // タグ情報を取得する
            $tag_id = Tag::where('name', '=', $name)
                ->value('id');
            // タグが見つからなかった場合
            if (empty($tag_id)) {
                continue;
            }
            $tag_id_list[] = $tag_id;
        }

        $this->tag_id_list = $tag_id_list;
    }

    /**
     * 保存された内部ラベルID一覧を取得.
     * @return array 内部ラベルID一覧
     */
    public function getSavedInnerLabelIdList() : array
    {
        return $this->program_labels()
            ->pluck('label_id')
            ->all();
    }

    /**
     * 内部ラベルID一覧取得.
     * @return array 内部ラベルID一覧
     */
    public function getInnerLabelIdListAttribute() : array
    {
        // 値を持っていた場合
        if (isset($this->appends['inner_label_id_list'])) {
            return $this->appends['inner_label_id_list'];
        }

        // ラベルIDリストを取得
        $this->appends['inner_label_id_list'] = isset($this->id) ? $this->getSavedInnerLabelIdList() : [];
        return $this->appends['inner_label_id_list'];
    }

    /**
     * 内部ラベルID一覧登録.
     * @param array $inner_label_id_list 内部ラベルID一覧
     */
    public function setInnerLabelIdListAttribute(array $inner_label_id_list)
    {
        $inner_label_id_list = array_values(array_unique($inner_label_id_list));
        sort($inner_label_id_list, SORT_NUMERIC);
        $this->appends['inner_label_id_list'] = $inner_label_id_list;
    }

    /**
     * ラベル取得.
     * @return string ラベル
     */
    public function getLabelsAttribute() : string
    {
        return implode(',', $this->label_id_list);
    }

    /**
     * ラベル登録.
     * @param string|null $labels ラベル
     */
    public function setLabelsAttribute(?string $labels)
    {
        $this->label_id_list = isset($labels) ? explode(',', $labels) : [];
    }

    /**
     * ラベルID一覧取得.
     * @return array ラベルID一覧
     */
    public function getLabelIdListAttribute() : array
    {
        $multi_join_label_id_list = Label::whereIn('name', config('map.multi_join_tag'))->pluck('id')->all();
        $label_id_list = array_values(array_diff($this->inner_label_id_list, $multi_join_label_id_list));
        sort($label_id_list, SORT_NUMERIC);
        return $label_id_list;
    }

    /**
     * ラベルID一覧を登録.
     * @param array $label_id_list ラベルID一覧
     */

    public function setLabelIdListAttribute(array $label_id_list)
    {
        array_push($label_id_list, Label::where('name', config('map.multi_join_tag')[$this->multi_join])->first()->id);
        $this->inner_label_id_list = $label_id_list;
    }

    /**
     * プログラム情報初期値取得.
     * @return Program プログラム情報
     */
    public static function getDefault() : Program
    {
        $program = new self();
        $program->multi_course = 0;
        $program->devices = 7;
        $program->carriers = 7;
        $program->shop_categories = 0;
        $program->list_show = 1;
        $now = Carbon::now();
        $program->test = 0;
        $program->priority = 100;
        $program->status = 2;
        $program->start_at = Carbon::create($now->year, $now->month, $now->day, $now->hour, $now->minute, 0);
        $program->stop_at = Carbon::create('9999', '12', '31', '23', '59', 0);
        return $program;
    }

    /**
     * タグの関連プログラム数を数える.
     * @param array|null $tag_id_list タグIDリスト
     */
    private function countAssociatedTag(?array $tag_id_list = null)
    {
        if (empty($tag_id_list)) {
            $tag_id_list = $this->tag_id_list;
        }
        if (empty($tag_id_list)) {
            return;
        }

        // タグごとのプログラム総数を取得
        $tag_total_map = ProgramTag::select(DB::raw('count(program_id) as total, tag_id'))
            ->where('status', '<>', 1)
            ->whereIn('tag_id', $tag_id_list)
            ->whereIn('program_id', function ($query) {
                $query->select('id')
                    ->from('programs')
                    ->where('status', '=', 0);
            })
            ->groupBy('tag_id')
            ->pluck('total', 'tag_id')
            ->all();

        foreach ($tag_id_list as $tag_id) {
            // プログラム総数を更新
            $tag = Tag::find($tag_id);
            $tag->program_total = isset($tag_total_map[$tag_id]) ? $tag_total_map[$tag_id] : 0;
            $tag->save();
        }
    }

    public function setSchedules($schedules) {
        $this->appends['schedule'] = $schedules;
    }

    /**
     * プログラム情報を保存.
     * @param int $admin_id 管理者ID
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function saveProgram(int $admin_id) : bool
    {
        // キーワードインデックス
        $this->keyword_index = implode("\n", $this->tag_list)."\n".
            implode("\n", [$this->title, strip_tags($this->description), strip_tags($this->detail)]);

        // トランザクション処理
        $program = $this;
        $res = DB::transaction(function () use ($admin_id, $program) {
            $is_create = !isset($program->id);

            $is_dirty = $program->isDirty();
            if ($is_dirty) {
                // 登録実行
                $program->save();
            }

            // 現在のタグID一覧
            $tag_id_list = $program->tag_id_list;
            // DBのタグID一覧
            $db_tag_id_list = $program->getSavedTagIdList();

            // タグのダーティチェック
            if (!(WrapPhp::count($tag_id_list) === WrapPhp::count($db_tag_id_list) &&
                count(array_diff($tag_id_list, $db_tag_id_list)) === 0)) {
                $is_dirty = true;

                // タグ関連付け削除処理
                if (!empty($db_tag_id_list)) {
                    $program->program_tags()
                        ->whereIn('tag_id', $db_tag_id_list)
                        ->update(['status' => 1, 'deleted_at' => Carbon::now()]);
                }

                // タグ関連付け処理
                if (!empty($tag_id_list)) {
                    $data_list = [];
                    foreach ($tag_id_list as $tag_id) {
                        $data_list[] = ['tag_id' => $tag_id, 'status' => 0];
                    }
                    $program->program_tags()->createMany($data_list);
                }
                $all_tag_id_list = array_unique(array_merge($tag_id_list, $db_tag_id_list));
                // タグの関連プログラム数を数える
                $program->countAssociatedTag($all_tag_id_list);
            }

            // 現在のラベルID一覧
            $inner_label_id_list = $program->inner_label_id_list;
            // DBのラベルID一覧
            $db_inner_label_id_list = $program->getSavedInnerLabelIdList();

            // ラベルのダーティチェック
            if (!(WrapPhp::count($inner_label_id_list) === WrapPhp::count($db_inner_label_id_list) &&
                count(array_diff($inner_label_id_list, $db_inner_label_id_list)) === 0)) {
                $is_dirty = true;

                // ラベル関連付け削除処理
                if (!empty($db_inner_label_id_list)) {
                    $program->program_labels()
                        ->whereIn('label_id', $db_inner_label_id_list)
                        ->update(['status' => 1, 'deleted_at' => Carbon::now()]);
                }

                // ラベル関連付け処理
                if (!empty($inner_label_id_list)) {
                    $data_list = [];
                    foreach ($inner_label_id_list as $label_id) {
                        $data_list[] = ['label_id' => $label_id, 'status' => 0];
                    }
                    $program->program_labels()->createMany($data_list);
                }
            }

            if ($is_dirty) {
                // ログを保存
                $program->saveEditLog($admin_id, $is_create ? '作成しました' : '更新しました');
            }
            // 更新作業の場合はここで終了
            if (!$is_create) {
                return true;
            }

            // アフィリエイト
            if (isset($program->affiriate)) {
                $affiriate = $program->affiriate;
                $affiriate->parent_id = $program->id;
                // 開始日時
                $affiriate->start_at = Carbon::now()->copy()->addYears(-1);
                $affiriate->save();
            }
            // コース
            $course_ids = [];
            if (isset($program->course)) {
                foreach ($program->course as $course) {
                    $course->program_id = $program->id;
                    // 開始日時
                    $course->save();
                    $course_ids[] = $course->id;
                }
            }
            // ポイント
            if (isset($program->point)) {
                foreach ($program->point as $point_no => $point) {
                    $point->program_id = $program->id;
                    if (isset($course_ids[$point_no]))
                    {
                        $point->course_id = $course_ids[$point_no];
                    }
                    // 開始日時
                    $point->savePointInner($admin_id);
                }
            }
            //question
            $question_ids = [];
            if (isset($program->question)) {
                foreach ($program->question as $question) {
                    $question->program_id = $program->id;
                    $question->save();
                    $question_ids[] = $question->id;
                }
            }

            // 予定
            if (isset($program->schedule)) {
                foreach ($program->schedule as $schedule_no => $schedule) {
                    $schedule->program_id = $program->id;
                    if (isset($course_ids[$point_no]))
                    {
                        $schedule->course_id = $course_ids[$schedule_no];
                    }
                    // 開始日時
                    $schedule->start_at = Carbon::now()->copy()->addYears(-1);
                    $schedule->saveProgramScheduleInner($admin_id);
                }
            }
            return true;
        });

        return $res;
    }

    /**
     * 状態を更新.
     * @param int $admin_id 管理者ID
     * @param int $status 状態
     */
    public function changeStatus(int $admin_id, int $status) : bool
    {
        $this->status = $status;
        if ($status == 1) {
            $this->deleted_at = Carbon::now();
        }
        $program = $this;
        // 保存実行
        return DB::transaction(function () use ($admin_id, $program) {
            // 保存
            $program->save();
            // ログを保存
            $program->saveEditLog($admin_id, $program->status == 1 ? '非公開にしました' : '公開にしました');
            // タグの関連プログラム数を数える
            $program->countAssociatedTag();
            return true;
        });
    }

    public function program_stocks()
    {
        return $this->hasOne(ProgramStock::class, 'program_id', 'id');
    }

    /**
     * update status program
     * @return boolean
     */
    public static function updateStatus($id, $status)
    {
        return static::find($id)->update(['updated_at' => Carbon::now(), 'status' => $status]);
    }

}
