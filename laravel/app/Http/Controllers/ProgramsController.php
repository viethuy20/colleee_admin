<?php
namespace App\Http\Controllers;

use App\AffReward;
use App\ProgramStock;
use App\ProgramStockLog;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Validator;
use Session;

use App\Affiriate;
use App\Asp;
use App\Attachment;
use App\Label;
use App\Paginators\BasePaginator;
use App\Point;
use App\Program;
use App\ProgramLabel;
use App\ProgramSchedule;
use App\Tag;
use Illuminate\Support\Facades\Log;
use WrapPhp;

/**
 * プログラム管理コントローラー.
 */
class ProgramsController extends Controller
{
    /**
     * プログラム検索.
     */
    public function index()
    {
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'sort' => 0, 'program_id' => null, 'title' => null, 'asp_id' => 0, 'accept_days' => null,
                'asp_affiriate_id' => null, 'ad_id' => null, 'start_at' => null, 'stop_at' => null, 'enable' => 0,
            ],
            function ($params) {
                $now = Carbon::now();

                $builder = Program::select('programs.*');
                // ID検索
                $builder = isset($params['program_id']) ? $builder->where('programs.id', '=', $params['program_id']) :
                    $builder;

                // 広告名
                if (isset($params['title'])) {
                    $builder = $builder->whereRaw(
                        '`programs`.`title` COLLATE utf8mb4_unicode_ci LIKE ?',
                        ['%'.addcslashes($params['title'], '\_%').'%']
                    );
                }

                // ASP検索
                if (!empty($params['asp_id']) || isset($params['accept_days']) ||
                    isset($params['asp_affiriate_id']) || isset($params['ad_id'])) {
                    $builder = $builder->join('affiriates', function ($join) use ($now) {
                        $join->on('programs.id', '=', 'affiriates.parent_id')
                            ->where('affiriates.parent_type', '=', Affiriate::PROGRAM_TYPE)
                            ->where('affiriates.status', '=', 0)
                            ->where('affiriates.stop_at', '>=', $now)
                            ->where('affiriates.start_at', '<=', $now);
                    });
                    $builder = !empty($params['asp_id']) ?
                        $builder->where('affiriates.asp_id', '=', $params['asp_id']) : $builder;

                    $builder = isset($params['accept_days']) ?
                        $builder->where('affiriates.accept_days', '=', $params['accept_days']) : $builder;

                    $builder = isset($params['asp_affiriate_id']) ?
                        $builder->where('affiriates.asp_affiriate_id', '=', $params['asp_affiriate_id']) : $builder;

                    $builder = isset($params['ad_id']) ? $builder->where('affiriates.ad_id', '=', $params['ad_id']) :
                        $builder;
                }

                // 開始日
                if (isset($params['start_at'])) {
                    try {
                        $start_at = Carbon::parse($params['start_at']);

                        $builder = $builder->whereBetween(
                            'programs.start_at',
                            [$start_at->copy()->startOfDay(), $start_at->copy()->endOfDay()]
                        );
                    } catch (\Exception $e) {
                        $builder = $builder->where(DB::raw('1 = 0'));
                    }
                }
                // 終了日
                if (isset($params['stop_at'])) {
                    try {
                        $stop_at = Carbon::parse($params['stop_at']);

                        $builder = $builder->whereBetween(
                            'programs.stop_at',
                            [$stop_at->copy()->startOfDay(), $stop_at->copy()->endOfDay()]
                        );
                    } catch (\Exception $e) {
                        $builder = $builder->where(DB::raw('1 = 0'));
                    }
                }
                //
                if ($params['enable'] == 1) {
                    $builder = $builder->where('programs.status', '=', 0)
                        ->where('programs.stop_at', '>=', $now)
                        ->where('programs.start_at', '<=', $now);
                }

                // ソート
                if ($params['sort'] != 0) {
                    $sort_v = ($params['sort'] > 0) ? 'asc' : 'desc';
                    $sort_map = [1 => 'id', 2 => 'title', 3 => 'start_at', 4 => 'stop_at', 5 => 'updated_at',
                        6 => 'deleted_at', 7 => 'priority'];
                    $builder = $builder->orderBy($sort_map[abs($params['sort'])], $sort_v);
                }
                $builder = $builder->orderBy('id', 'desc');

                return $builder;
            },
            20
        );

        // ASPマスタ取得
        $asp_map = Asp::where('status', '=', 0)
            ->where('type', '=', ASP::PROGRAM_TYPE)
            ->pluck('name', 'id')
            ->all();
        return view('programs.index', ['paginator' => $paginator, 'asp_map' => $asp_map,]);
    }

    /**
     * プログラム情報作成.
     */
    public function create()
    {
        return $this->edit(Program::getDefault());
    }

    /**
     * プログラム情報更新.
     * @param Program $program プログラムEloquent
     */
    public function edit(Program $program)
    {
        // プログラム初期値・入力値を取得
        $program_map = $program->only(['id', 'status', 'test', 'title', 'description',
            'detail', 'multi_join', 'fee_condition', 'list_show', 'priority', 'memo',
            'device', 'carrier', 'shop_category', 'tags', 'labels', 'multi_course','questions',
            'ad_title', 'ad_detail',
        ]);
        $start_at = $program->start_at->format('Y-m-d H:i');
        $start_at_list =  explode(' ',$start_at);
        $program_map['start_at_date'] = $start_at_list[0];
        $program_map['start_at_time'] = $start_at_list[1];
        $stop_at = $program->stop_at->format('Y-m-d H:i');
        $stop_at_list =  explode(' ',$stop_at);
        $program_map['stop_at_date'] = $stop_at_list[0];
        $program_map['stop_at_time'] = $stop_at_list[1];
        $datas = ['program' => $program_map,];

        if (isset($program->id)) {
            // 公開日時
            $released_at = $program->released_at->format('Y-m-d H:i');
            $released_at_list =  explode(' ',$released_at);
            $datas['program']['released_at_date'] = $released_at_list[0];
            $datas['program']['released_at_time'] = $released_at_list[1];

            // マルチコース情報取得
            if ($program->multi_course == 1) {
                $datas['course_list'] = $program->courses()
                    ->orderBy('id', 'asc')
                    ->get()
                    ->pluck(null, 'id');
            }
            // ポイント履歴取得
            $datas['point_list'] = $program->points()
                ->where('status', '=', 0)
                ->where('stop_at', '>=', Carbon::today()->copy()->addDays(-400))
                ->orderByRaw('course_id asc, start_at desc')
                ->get()->groupBy('course_id');
            // アフィリエイト履歴取得
            $datas['affiriate_list'] = $program->affiriates()
                ->where('status', '=', 0)
                ->orderBy('start_at', 'desc')
                ->get();

            $datas['question_list'] = $program->questions()
                ->orderBy('id', 'asc')
                ->get()
                ->pluck(null, 'id');
            //get program stock
            $programStock = ProgramStock::where('program_id', $program->id)->whereNull('deleted_at')->orderBy('id', 'desc')->first();
            $datas['program']['stock_cv'] = $programStock->stock_cv ?? '';
            if (!empty($programStock->note)) {
                $datas['program']['note'] = explode('; ',$programStock->note);
            }
            $datas['program']['updated_at'] = $programStock->updated_at ?? Carbon::now()->format('Y-m-d');
        } else {
            // アフィリエイト情報取得
            $datas['affiriate'] = $program->affiriate->only(['id', 'asp_id', 'ad_id', 'asp_affiriate_id', 'url',
                'img_url', 'accept_days', 'give_days', 'memo',]);
            // ポイント初期値・入力値を取得
            $point_list = [];
            foreach ($program->point as $point) {
                $point_list[] = $point->only(['id', 'fee', 'all_back', 'fee_type', 'bonus','rewards']);
            }
            $datas['point_list'] = $point_list;
            // プログラム予定初期値・入力値を取得
            $program_schedule_list = [];
            foreach ($program->schedule as $program_schedule) {
                $program_schedule_list[] = $program_schedule->only(['id', 'reward_condition', 'memo',]);
            }
            $datas['program_schedule'] = $program_schedule_list;
        }

        // ASPマスタ取得
        $datas['asp_map'] = Asp::where('status', '=', 0)
            ->where('type', '=', ASP::PROGRAM_TYPE)
            ->pluck('name', 'id')
            ->all();
        // 利用頻度の高いタグ名リストを取得
        $datas['high_use_tag_list'] = Tag::orderBy('program_total', 'desc')
            ->where('status', '<>', 1)
            ->whereNotIn('name', array_values(config('map.multi_join_tag')))
            ->limit(500)
            ->pluck('name')
            ->all();

        //参加上限以外のラベルリスト
        $label_type_list = array_keys(config('map.label_type'));
        unset($label_type_list[Label::TYPE_ENTRY_MAX-1]);

        //  全ラベルのリストを取得
        $all_label_list = Label::whereIn('type', $label_type_list)->get();
        // 親ラベルリストを作成
        $parent_label_list = $all_label_list->filter(function ($label, $key) {
            return $label->label_id == 0;
        });

        $parent_label_map = $parent_label_list->groupBy('type')->all();
        $label_data_map = [];
        $label_options_attributes = [];
        foreach ($parent_label_map as $type => $label_list) {
            // 親ラベルを登録
            $label_data_map[$type][0] = $label_list->pluck('name', 'id')->all();
            // 小ラベルを再帰的に登録
            $index = 1;
            $label_id_list = $label_list->pluck('id')->all();
            while (true) {
                // 子ラベルを取得
                $child_label_list = $all_label_list->filter(function ($label, $key) use ($label_id_list) {
                    return in_array($label->label_id, $label_id_list);
                });

                // 子ラベルが存在しない場合、終了
                if ($child_label_list->isEmpty()) {
                    break;
                }
                // 子ラベルを登録
                $label_data_map[$type][$index] = $child_label_list->pluck('name', 'id')->all();
                $label_options_attributes = $label_options_attributes +
                    $child_label_list->pluck('label_id', 'id')->all();

                $index = $index + 1;
                $label_id_list = $child_label_list->pluck('id')->all();
            }
        }
        $datas['label_data_map'] = $label_data_map;
        $datas['label_options_attributes'] = $label_options_attributes;

        return view('programs.edit', $datas);
    }

    /**
     * プログラム情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $device_keys = array_keys(config('map.device'));
        $carrier_keys = array_keys(config('map.carrier'));
        $shop_category_keys = array_keys(config('map.shop_category'));
        $stock_cv = $request->input('stock_cv');
        $stock_note = $request->input('note') ?? [];
        $stock_note = array_filter($stock_note, function ($value) {
            return $value !== null;
        });
        if ($stock_note){
            $note = implode('; ', $stock_note);
        }
        else{
            $note = null;
        }


        $pointValidatateMsg = [];

        $validateRules = [
            'id' => ['nullable', 'integer'],
            'test' => ['required', 'integer', 'in:0,1'],
            'title' => ['required', 'max:256'],
            'description' => ['required'],
            'device.*' => ['required', 'integer', 'in:'. implode(',', $device_keys)],
            'carrier.*' => ['required', 'integer', 'in:'. implode(',', $carrier_keys)],
            'multi_join' => ['required', 'integer', 'in:0,1,2'],
            'fee_condition' => ['required', 'max:256'],
            'shop_category.*' => ['required', 'integer', 'in:'. implode(',', $shop_category_keys)],
            'list_show' => ['required', 'integer', 'in:0,1'],
            'priority' => ['required', 'integer', 'between:1,999'],
            'start_at_date' => ['required', 'date_format:"Y-m-d"'],
            'start_at_time' => ['required', 'date_format:"H:i"'],
            'stop_at_date' => ['required', 'date_format:"Y-m-d"'],
            'stop_at_time' => ['required', 'date_format:"H:i"'],
            'course' => ['array'],
            'questions' => ['array'],
        ];

        // プログラム情報
        if ($request->filled('id')) {
            $program = Program::find($request->input('id'));

            // 公開日時のバリデーション
            $validateRules['released_at_date'] = ['required', 'date_format:"Y-m-d"',];
            $validateRules['released_at_time'] = ['required', 'date_format:"H:i"',];
        } else {
            $program = Program::getDefault();
            // 画像IDリストを登録
            $program->img_ids = $request->input('img_ids');

            $asp_id = $request->input('affiriate.asp_id');

            if ($request->input('course')) {
                $validateRules['course'] = ['required', 'array', 'min:1',];
                $validateRules['course.*.aff_course_id'] = ['nullable','distinct', 'max:256',];
                $validateRules['course.*.course_name'] = ['required', 'distinct', 'max:256',];
                $validateRules['course.*.priority'] = ['integer', 'between:1,999',];
            }

            if ($request->input('questions')) {
                $validateRules['questions.*.question'] = ['required'];
                $validateRules['questions.*.answer'] = ['required'];
                $validateRules['questions.*.disp_order'] = ['integer', 'between:1,999',];
            }

            // 新規登録時のバリデーション
            $validateRules['affiriate.asp_id'] = ['required', 'integer',];
            $validateRules['affiriate.asp_affiriate_id'] = ['required',
                Rule::unique('affiriates', 'asp_affiriate_id')->where(function ($query) use ($asp_id) {
                    $query->where('asp_id', '=', $asp_id);
                }),];
            $validateRules['affiriate.ad_id'] = ['required',
                Rule::unique('affiriates', 'asp_affiriate_id')->where(function ($query) use ($asp_id) {
                    $query->where('asp_id', '=', $asp_id);
                }),];
            $validateRules['affiriate.url'] = ['required', 'url', 'secure_resource',
                'regex:/^(?=.*'.preg_quote(Affiriate::COLLEEE_USERID_REPLACE).').*$/',];
            $validateRules['affiriate.img_url'] = ['required', 'url', 'secure_resource',];
            $validateRules['affiriate.accept_days'] = ['required', 'integer',];
            if ($request->input('affiriate.accept_days') == 0) {
                $validateRules['affiriate.accept_speedy'] = ['integer', 'required', 'in:1',];
            } else {
                $validateRules['affiriate.accept_speedy'] = ['integer', 'nullable', 'in:0',];
            }
            $validateRules['affiriate.give_days'] = ['nullable', 'integer',];

            // ポイントは条件別動的配列なので、別途バリデーションを設定
            $pointValidatateMsg['point'] = 'ポイント';
            $validateRules['point'] = ['required', 'array', 'min:1',];
            foreach ($request->input('point') as $idx => $point) {
                if ($point['fee_type'] == 2) {
                    // 定率の場合
                    $validateRules["point.$idx.fee"] = ['required', 'numeric', 'min:0.1', 'max:100',];
                    $validateRules["point.$idx.rewards"] = ['nullable', 'numeric', 'min:0.1', 'max:100',];

                } else {
                    // 定額の場合
                    $validateRules["point.$idx.fee"] = ['required', 'integer', 'min:1',];
                    $validateRules["point.$idx.rewards"] = ['nullable', 'integer', 'min:1',];
                }
                $validateRules["point.$idx.all_back"] = ['required', 'integer', 'in:0,1',];
                $validateRules["point.$idx.fee_type"] = ['required', 'integer', 'in:1,2',];
                $validateRules["point.$idx.bonus"] = ['required', 'integer', 'in:0,1',];

                $pointValidatateMsg["point.$idx.fee"] = 'ユーザー報酬';
                $pointValidatateMsg["point.$idx.rewards"] = '報酬額';
                $pointValidatateMsg["point.$idx.all_back"] = '100%還元';
                $pointValidatateMsg["point.$idx.fee_type"] = '成果タイプ';
                $pointValidatateMsg["point.$idx.bonus"] = 'ボーナス';
            }
            $validateRules['program_schedule'] = ['required', 'array', 'min:1',];
            $validateRules['program_schedule.*.reward_condition'] = ['required',];
        }
        //
        $this->validate(
            $request,
            $validateRules,
            [],
            array_merge(
                [
                    'id' => 'ID',
                    'test' => 'テスト',
                    'title' => 'タイトル',
                    'description' => 'ディスクリプション',
                    'detail' => '詳細',
                    'img_ids' => '画像ID',
                    'device.*' => '対象デバイス',
                    'carrier.*' => '対象キャリア',
                    'multi_join' => '複数参加対象',
                    'fee_condition' => '成果条件',
                    'shop_category.*' => 'ショップカテゴリ',
                    'list_show' => 'リスト公開',
                    'priority' => 'ウェイト',
                    'start_at_date' => '掲載開始日',
                    'start_at_time' => '掲載開始時',
                    'stop_at_date' => '掲載終了日',
                    'stop_at_time' => '掲載終了時',
                    'released_at_date' => '公開日',
                    'released_at_time' => '公開時',
                    'memo' => 'Memo',

                    'affiriate.id' => 'アフィリエイトID',
                    'affiriate.asp_id' => 'ASP',
                    'affiriate.asp_affiriate_id' => 'データ連携ID',
                    'affiriate.ad_id' => 'ASP別検索ID',
                    'affiriate.url' => '遷移先',
                    'affiriate.img_url' => '画像URL',
                    'affiriate.accept_days' => '獲得時期目安',
                    'affiriate.accept_speedy' => '即時承認',
                    'affiriate.give_days' => '予定反映目安',
                    'affiriate.memo' => 'Memo',

                    'course' => 'コース',
                    'course.*.aff_course_id' => '連携コースID',
                    'course.*.course_name' => 'コース名',
                    'course.*.priority' => '表示順',

                    'questions' => '質問',
                    'questions.*.question' => '質問名',
                    'questions.*.answer' => '回答',
                    'questions.*.disp_order' => '表示順',

                    'program_schedule.*.reward_condition' => '獲得条件',
                ],
                $pointValidatateMsg
            )
        );

        // プログラム
        $program->fill($request->only(['test', 'title', 'description', 'detail',
            'multi_join', 'fee_condition', 'list_show', 'priority', 'recipe_ids',
            'memo', 'multi_course' , 'ad_title', 'ad_detail',
        ]));
        $program->device = $request->input('device');
        $program->carrier = $request->input('carrier');
        $program->shop_category = $request->input('shop_category');
        $program->tags = $request->input('tags');
        $program->labels = $request->input('labels');
        // 開始日時
        $start_at = $request->input('start_at_date').' '.$request->input('start_at_time');
        $program->start_at = Carbon::parse($start_at.':00');
        // 終了日時
        $stop_at = $request->input('stop_at_date').' '.$request->input('stop_at_time');
        $program->stop_at = Carbon::parse($stop_at.':00');

        if (!isset($program->id)) {
            // 公開日時
            $program->released_at = $program->start_at;

            // 関連初期データ取得
            $associate_data = $request->only(['affiriate.asp_id', 'affiriate.asp_affiriate_id',
                'affiriate.ad_id','affiriate.url', 'affiriate.img_url', 'affiriate.accept_days',
                'affiriate.give_days','affiriate.memo', 'course', 'point', 'program_schedule',
                'questions',
            ]);
            // アフィリエイト初期データ登録
            $program->affiriate->fill($associate_data['affiriate']);

            // コース初期データ取得
            $courses = array();
            if (isset($associate_data['course']))
            {
                foreach ($associate_data['course'] as $reqCourse) {
                    $courses[] = $program->defaultCourse->fill($reqCourse);
                }
                $program->setCourses($courses);
            }

            //question
            $questions = array();
            if (isset($associate_data['questions']))
            {
                foreach ($associate_data['questions'] as $reqQuestion) {
                    $questions[] = $program->defaultQuestion->fill($reqQuestion);
                }
                $program->setQuestions($questions);
            }
            // ポイント初期データ取得
            $points = array();
            foreach ($associate_data['point'] as $reqPoint) {
                $point = $program->defaultPoint->fill($reqPoint);
                if ($point->fee_type == 2) {
                    $point->point = null;
                    $point->rate = floatval($reqPoint['fee']) * 0.01;
                    $point->reward_amount_rate = floatval($reqPoint['rewards']) * 0.01;
                    $point->reward_amount = null;
                } else {
                    $point->point = $reqPoint['fee'];
                    $point->rate = null;
                    $point->reward_amount = $reqPoint['rewards'];
                    $point->reward_amount_rate = null;
                }
                $points[] = $point;
            }
            $program->setPoints($points);
            $program->benefit = count(array_filter($program->point, function ($point) {
                return $point->bonus == 1;
            })) > 0 ? 1 : 0;

            // プログラム予定初期データ登録
            $schedules = array();
            foreach ($associate_data['program_schedule'] as $schedule) {
                $schedules[] = ProgramSchedule::getDefault()->fill($schedule);
            }
            $program->setSchedules($schedules);
        } else {
            // 公開日時
            $released_at = $request->input('released_at_date').' '.$request->input('released_at_time');
            $program->released_at = Carbon::parse($released_at.':00');
        }

        // 保存実行
        $res = $program->saveProgram(Auth::user()->id);

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'プログラム情報の編集に失敗しました');
        }

        //save stock
        $programStock = ProgramStock::query()
            ->with('programStockBatchLog')
            ->where('program_id', $program->id)
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();

        if (empty($programStock) && !empty($stock_cv)) {
            $programStock = ProgramStock::getDefault($program->id, $stock_cv, $note);
            $programStock->save();

        } else if(!empty($programStock)) {
            $programStockBatchLog = $programStock->programStockBatchLog;

            // Run batch
            if (
                !empty($programStockBatchLog) &&
                $programStockBatchLog->status_notify == 0 &&
                $programStockBatchLog->time_run_batch == $programStock->updated_at
            ) {
                /**
                 * User edit stock no => show alert
                 * User not edit sotck => not show alert
                 */

                $oldStockCv = $request->old_stock_cv;
                $newStockCv = $request->stock_cv;
                $dbStockCv = $programStock->stock_cv;

                // User edit stock
                if ($newStockCv != $oldStockCv) {
                    Session::flash("error-cv-in-database", [
                        'message' => '編集中に在庫数が更新されました。最新の情報を確認してから再度編集を行ってください。',
                    ]);
                }

                $programStockBatchLog->update(['status_notify' => 1]);
            }
            // Not run batch => update normal
            else {
                $programStock->update(['stock_cv' => $stock_cv, 'note' => $note, 'updated_at' => Carbon::now()]);
            }
        }

        // 初期登録の際は添付ファイルを有効にする
        if (!$request->filled('id') && isset($program->img_ids)) {
            Attachment::enable(explode(',', $program->img_ids));
        }
        return redirect(route('programs.edit', ['program' => $program]))
            ->with('message', 'プログラム情報の編集に成功しました');
    }

    /**
     * プログラム公開.
     * @param Program $program プログラム情報
     */
    public function enable(Program $program)
    {
        // 状態を確認
        if (!in_array($program->status, [1, 2], true)) {
            abort(404, 'Not Found.');
        }
        $res = $program->changeStatus(Auth::user()->id, 0);
        // 失敗した場合
        $message = empty($res) ? 'プログラム情報の公開に失敗しました' : 'プログラム情報の公開に成功しました';

        return redirect()->back()->with('message', $message);
    }

    /**
     * プログラム非公開.
     * @param Program $program プログラム情報
     */
    public function destroy(Program $program)
    {
        // 非公開実行
        $res = $program->changeStatus(Auth::user()->id, 1);
        return redirect()
            ->back()
            ->with('message', empty($res) ? 'プログラム情報の非公開に失敗しました' : 'プログラム情報の非公開に成功しました');
    }

    /**
     * 有効広告判定Ajax.
     * @param Request $request {@link Request}
     */
    public function ajaxEnableProgram(Request $request)
    {
        //
        $validator = Validator::make(
            $request->all(),
            ['program_id' => ['required', 'integer',],],
            [],
            []
        );
        if ($validator->fails()) {
            abort(404, 'Not Found.');
        }

        // 広告取得
        $program = Program::find($request->input('program_id'));

        if (!isset($program)) {
            abort(404, 'Not Found.');
        }
        $data['program_id'] = $program->id;
        $data['title'] = $program->title;
        return $data;
    }

    public function ajaxShowCourse(Request $request)
    {
        $course_no = $request->input('id');

        $course_list = [];
        $course_list[$course_no] = [
            'aff_course_id' => '',
            'course_name' => '',
        ];

        return view('elements.programs_course_list', compact('course_no', 'course_list'));
    }

    public function ajaxAddCourse(Request $request)
    {
        $course_no = $request->input('id');
        return view('elements.programs_course_detail', compact('course_no'));
    }

    public function ajaxAddPoint(Request $request)
    {
        $course_no = $request->input('id');
        return view('elements.programs_point_layout', compact('course_no'));
    }

    public function ajaxAddNoteStockCV(Request $request)
    {
        $note_no = $request->input('id');
        return view('elements.programs_stockcv_note_layout', compact('note_no'));
    }

    public function ajaxAddQuestion(Request $request)
    {
        $question_no = $request->input('data')['id'];
        $max = $request->input('max');
        return view('elements.programs_question_layout', compact('question_no','max'));
    }

    public function ajaxAddLabelTag(Request $request)
    {
        $label_ids = explode(',', $request->input('label_ids'));
        $tags = explode(',', $request->input('tags'));
        $add_label_id = intval($request->input('add_label_id'), 10);
        array_push($label_ids, $add_label_id);
        $label =  Label::find($add_label_id);
        $parent_id =  $label->label_id;
        $tag_list = array_merge($tags, $label->tag_list);
        // 親ラベルを再帰的に取得
        while ($parent_id > 0) {
            // 親ラベルを追加
            array_push($label_ids, $parent_id);
            // 親レベルの親ラベルを検索する
            $parent_id = Label::find($parent_id)->label_id;
        }
        array_unique($label_ids);
        if (!isset($label_ids)) {
            $data = (object)['labelList' => [], 'tagList' => $tag_list];
            return response()->json($data, 200);
        }
        $label_list = Label::select('id', 'name')->whereIn('id', $label_ids)->get();
        array_unique($tag_list);
        $data = (object)['labelList' => $label_list, 'tagList' => $tag_list];
        return response()->json($data, 200);
    }
    public function ajaxGetUrl(Request $request)
    {
        $asp_id = $request->input('id');
        $asp = Asp::where('id', $asp_id)->first();

        if (empty($asp)) {
            return response()->json([
                'url' => '',
                'error' => true
            ], 200);
        }

        return response()->json([
            'url' => $asp->url,
            'error' => false
        ], 200);

    }

    public function ajaxRemoveLabelTag(Request $request)
    {
        $label_ids = explode(',', $request->input('label_ids'));
        $remove_label_id = $request->input('remove_label_id');
        $label = Label::find($remove_label_id);
        $remove_key = array_search($remove_label_id, $label_ids);
        unset($label_ids[$remove_key]);
        array_values($label_ids);
        //選択されたラベルが親ラベルだった場合、子ラベルも消す
        $child_id_list = $label->child_list->pluck('id')->all();
        // 子ラベルを再帰的に取得
        while (!empty($child_id_list)) {
            // 子ラベルを削除する
            $label_ids = array_diff($label_ids, $child_id_list);
            // 子ラベルの子ラベルを検索する
            $child_id_list = Label::whereIn('label_id', $child_id_list)->pluck('id')->all();
        }
        // 選択されたラベルが子ラベルで、その親ラベルの子ラベルが存在しない場合親ラベルも消す。
        $parent_id = $label->label_id;
        while ($parent_id > 0) {
            // 親ラベルの子ラベルを検索する
            $child_id_list = Label::where('label_id', '=', $parent_id)->pluck('id')->all();
            if (empty(array_intersect($label_ids, $child_id_list))) {
                $remove_key = array_search($parent_id, $label_ids);
                unset($label_ids[$remove_key]);
                array_values($label_ids);
            }
            // 親をさかのぼる
            $parent_id = Label::find($parent_id)->label_id;
        }
        if (!isset($label_ids)) {
            return response()->json((object)['labelList' => [], 200]);
        }

        $label_list = Label::select('id', 'name')->whereIn('id', $label_ids)->get();
        return response()->json((object)['labelList' => $label_list, 200]);
    }

    public function copy(Request $request) {
        $program = Program::find($request->input('refererProgramId'));

        if (!isset($program)) {
            return redirect(route('programs.create'))
                ->withInput()
                ->withErrors(['refererProgramId' => 'コピー元のプログラムが存在しません。']);
        }

        // プログラム初期値・入力値を取得
        $program_map = $program->only([
            'test',
            'title',
            'description',
            'detail',
            'multi_join',
            'fee_condition',
            'list_show',
            'priority',
            'recipe_ids',
            'device',
            'carrier',
            'shop_category',
            'tags',
            'labels',
            'multi_course',
        ]);
        $program_map['start_at'] = $program->start_at->format('Y-m-d H:i');
        $program_map['stop_at'] = $program->stop_at->format('Y-m-d H:i');
        $datas = $program_map;

        // アフィリエイト情報取得
        $datas['affiriate'] = $program->affiriate->only([
            'asp_id',
            'url',
            'img_url',
            'accept_days',
            'give_days',
        ]);

        // ポイント&コース
        $point_list = [];
        $course_list = [];
        foreach ($program->point as $point) {
            $point_list[] = $point->only(['id', 'all_back', 'fee_type', 'bonus',]);
            if ($program->multi_course == 1) {
                $course_list[] = $point->course->only(['aff_course_id', 'course_name', 'priority']);
            }
        }
        $datas['point'] = $point_list;
        if (WrapPhp::count($course_list) > 0) {
            $datas['course'] = $course_list;
            $datas['max_layout_no'] = WrapPhp::count($course_list);
        }

        // プログラム予定初期値・入力値を取得
        $program_schedule_list = [];
        foreach ($program->schedule as $program_schedule) {
            $program_schedule_list[] = $program_schedule->only(['id', 'reward_condition', 'memo',]);
        }
        $datas['program_schedule'] = $program_schedule_list;

        // 在庫CV
        $programStock = ProgramStock::where('program_id', $program->id)->whereNull('deleted_at')->orderBy('id', 'desc')->first();
        $datas['stock_cv'] = $programStock->stock_cv ?? '';

        return redirect(route('programs.create'))
            ->withInput($datas);
    }
}

