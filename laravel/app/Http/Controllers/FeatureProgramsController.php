<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Content;
use App\FeatureProgram;
use App\FeatureSubCategory;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * 特集広告管理コントローラー.
 */
class FeatureProgramsController extends Controller
{
    const SPOT_FEATURE_CATEGORY = 13;

    /**
     * Viewデータ取得.
     * @return array データ
     */
    private function getBaseDatas() : array
    {
        // コンテンツマスタ（特集カテゴリ）取得
        $feature_category_map = Content::where('spot_id', '=', self::SPOT_FEATURE_CATEGORY)
            ->pluck('title', 'id')
            ->all();

        // 特集サブカテゴリマスタ取得
        $sub_category_map = FeatureSubCategory::orderBy('feature_id', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('title', 'id')
            ->all();

        return ['feature_category_map' => $feature_category_map, 'sub_category_map' => $sub_category_map];
    }
    
    /**
     * 特集広告情報検索.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        // ページネーション作成
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'feature_id' => null, 'sub_category_id' => null],
            function ($params) {
                $builder = FeatureProgram::select('feature_programs.*');
                // 特集カテゴリ検索
                if (!empty($params['feature_id'])) {
                    $builder = $builder->where('feature_id', '=', $params['feature_id']);
                }
                
                // 特集サブカテゴリ検索
                if (!empty($params['sub_category_id'])) {
                    $builder = $builder->where('sub_category_id', '=', $params['sub_category_id']);
                }

                $builder = $builder->orderBy('id', 'desc');
                return $builder;
            },
            20
        );

        $datas = $this->getBaseDatas();
        // ページネーション
        $datas['paginator'] = $paginator;

        return view('feature_programs.index', $datas);
    }
    
    /**
     * 特集広告情報作成.
     */
    public function create()
    {
        return $this->edit(FeatureProgram::getDefault());
    }
    
    /**
     * 特集広告情報更新.
     * @param FeatureProgram $feature_program 特集広告
     */
    public function edit(FeatureProgram $feature_program)
    {
        // 特集広告初期値・入力値を取得
        $feature_program_map = $feature_program->only(['id', 'feature_id', 'sub_category_id', 'program_id', 'detail',
            'priority', 'program']);

        $datas = $this->getBaseDatas();
        $datas['feature_program'] = $feature_program_map;

        return view('feature_programs.edit', $datas);
    }
    
    /**
     * 特集広告情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer',],
                'feature_id' => ['required', 'integer',],
                'sub_category_id' => ['required', 'integer',],
                'program_id' => ['required', 'integer', Rule::exists('programs', 'id'),],
                'detail' => ['required', 'max:256',],
                'priority' => ['required', 'integer',],
            ],
            [],
            [
                'id' => 'ID',
                'feature_id' => '特集カテゴリID',
                'sub_category_id' => '特集サブカテゴリ',
                'program_id' => 'プログラム',
                'detail' => '詳細',
                'priority' => '表示順',
            ]
        );

        // 特集広告情報
        $feature_program = null;
        // 初期データ取得
        if ($request->filled('id')) {
            $feature_program = FeatureProgram::find($request->input('id'));
        } else {
            $feature_program = FeatureProgram::getDefault();
        }

        // 特集広告
        $feature_program->fill($request->only(['feature_id', 'sub_category_id', 'program_id', 'detail', 'priority']));
        
        // トランザクション処理
        $res = DB::transaction(function () use ($feature_program) {
            // 登録実行
            $feature_program->save();
            return true;
        });
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '特集広告情報の編集に失敗しました');
        }
        
        return redirect(route('feature_programs.edit', ['feature_program' => $feature_program]))
            ->with('message', '特集広告情報の編集に成功しました');
    }

    private function changeStatus(FeatureProgram $feature_program, bool $enable)
    {
        if ($enable) {
            $action = '公開';
            $feature_program->status = 0;
        } else {
            $action = '非公開';
            $feature_program->status = 1;
        }

        // トランザクション処理
        $res = DB::transaction(function () use ($feature_program) {
            // 登録実行
            $feature_program->save();
            return true;
        });

        // 失敗した場合
        $message = empty($res) ? '特集広告情報の'.$action.'に失敗しました' : '特集広告情報の'.$action.'に成功しました';

        return redirect()
            ->back()
            ->with('message', $message);
    }

    /**
     * 特集広告公開.
     * @param FeatureProgram $feature_program 特集広告
     */
    public function enable(FeatureProgram $feature_program)
    {
        return $this->changeStatus($feature_program, true);
    }
    
    /**
     * 特集広告非公開.
     * @param FeatureProgram $feature_program 特集広告
     */
    public function destroy(FeatureProgram $feature_program)
    {
        return $this->changeStatus($feature_program, false);
    }

    /**
     * 特集サブカテゴリAjax.
     * @param Request $request {@link Request}
     */
    public function ajaxSubCategory(Request $request)
    {
        // 特集サブカテゴリ取得
        $sub_category_map = FeatureSubCategory::ofCategory($request->input('feature_id'))
            ->pluck('title', 'id')
            ->all();

        $data['sub_category_map'] = $sub_category_map;
        return $data;
    }
}
