<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\Question;
use Illuminate\Support\Facades\Auth;

/**
 * アンケート管理コントローラー.
 */
class QuestionsController extends Controller
{
    /**
     * デイリーアンケート検索.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        // ページネーション作成
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'question_id' => null, 'title' => null],
            function ($params) {
                $builder = new Question();
                // アンケートID検索
                if (isset($params['question_id'])) {
                    $builder = $builder->where('id', '=', $params['question_id']);
                }
                // タイトル検索
                if (!empty($params['title'])) {
                    $builder = $builder->where('title', 'LIKE', '%'.addcslashes($params['title'], '\_%').'%');
                }
                $builder = $builder->orderBy('id', 'desc');
                return $builder;
            },
            50
        );
        return view('questions.index', ['paginator' => $paginator]);
    }
    
    /**
     * アンケート情報作成.
     */
    public function create()
    {
        return $this->edit(Question::getDefault());
    }
    
    /**
     * アンケート情報更新.
     * @param Question $question アンケート
     */
    public function edit(Question $question)
    {
        // アンケート初期値・入力値を取得
        $question_map = $question->only(['id', 'title', 'answer']);
        return view('questions.edit', ['question' => $question_map]);
    }
    
    /**
     * アンケート情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer',],
                'title' => ['required', 'max:256',],
                'answer.*.id' => ['required', 'integer',],
                'answer.*.label' => ['nullable', 'max:256',],
            ],
            [],
            [
                'id' => 'ID',
                'title' => 'タイトル',
                'answer.*.id' => '回答ID',
                'answer.*.label' => '回答',
            ]
        );
    
        // アンケート情報
        $question = null;
        // 初期データ取得
        if ($request->filled('id')) {
            $question = Question::find($request->input('id'));
        } else {
            $question = Question::getDefault();
        }

        // アンケート
        $question->title = $request->input('title');
        $question->answer = $request->input('answer');

        // トランザクション処理
        $res = DB::transaction(function () use ($question) {
            // 登録実行
            $question->save();
            return true;
        });
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'アンケート情報の編集に失敗しました');
        }
        
        return redirect(route('questions.edit', ['question' => $question]))->with('message', 'アンケート情報の編集に成功しました');
    }
    
    /**
     * アンケート公開.
     * @param Request $request {@link Request}
     */
    public function enable(Request $request)
    {
        $validateAttributes = ['id' => 'ID', 'start_at' => '開始日時', 'stop_at' => '終了日時',];

        //
        $this->validate(
            $request,
            [
                'id' => ['required', 'integer',],
                'start_at' => ['required', 'date_format:"Y-m-d"',],
                'stop_at' => ['nullable', 'date_format:"Y-m-d"',],
                ],
            [],
            $validateAttributes
        );

        // アンケート情報
        $question = Question::find($request->input('id'));
        // アンケート情報が存在しなかった場合
        if (!isset($question->id)) {
            abort(404, 'Not Found.');
        }

        $start_at = Carbon::parse($request->input('start_at').' 00:00:00');
        
        // 開始日時と終了日時を確認
        $this->validate(
            $request,
            [
                'start_at' => Rule::unique('questions')->where(function ($query) {
                    $query->where('status', '=', 0);
                })
            ],
            [],
            $validateAttributes
        );
        // 開始日時
        $question->start_at = $start_at;
        // 終了日時
        $question->stop_at = $start_at->copy()->endOfDay();
        
        $question->status = 0;
        
        // 保存実行
        $res = DB::transaction(function () use ($question) {
            // 保存
            $question->save();
            return true;
        });

        return redirect()
            ->back()
            ->with('message', empty($res) ? 'アンケート情報の公開に失敗しました' : 'アンケート情報の公開に成功しました');
    }
    
    /**
     * アンケート非公開.
     * @param Question $question アンケート
     */
    public function destroy(Question $question)
    {
        // 保存実行
        $question->status = 1;
        $question->deleted_at = Carbon::now();
        // トランザクション処理
        $res = DB::transaction(function () use ($question) {
            $today = Carbon::today()->startOfDay();
            if ($question->status == 0 && $today->lte($question->start_at)) {
                // 次のアンケート一覧を取得
                $next_question_list = Question::where('status', '=', 0)
                    ->orderBy('start_at', 'asc')
                    ->get();

                // 開催期間を1日早める
                if (!$next_question_list->isEmpty()) {
                    foreach ($next_question_list as $next_question) {
                        // 開始日時
                        $next_question->start_at = $next_question->start_at->copy()->addDays(1);
                        // 終了日時
                        $next_question->stop_at = $next_question->stop_at->copy()->addDays(1);
                        
                        $next_question->save();
                    }
                }
            }
            
            // 登録実行
            $question->save();
            return true;
        });

        return redirect()
            ->back()
            ->with('message', empty($res) ? 'アンケート情報の非公開に失敗しました' : 'アンケート情報の非公開に成功しました');
    }
}
