<?php
namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

use App\Content;
use App\FeatureSubCategory;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * 特集サブカテゴリ管理コントローラー.
 */
class FeatureSubCategoriesController extends Controller
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

        return ['feature_category_map' => $feature_category_map];
    }

    /**
     * 特集サブカテゴリ検索.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        // ページネーション作成
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'feature_id' => null],
            function ($params) {
                $builder = FeatureSubCategory::select('feature_sub_categories.*');
                // 特集カテゴリ検索
                if (!empty($params['feature_id'])) {
                    $builder = $builder->where('feature_id', '=', $params['feature_id']);
                }

                $builder = $builder->orderBy('id', 'desc');
                return $builder;
            },
            20
        );

        $datas = $this->getBaseDatas();
        // ページネーション
        $datas['paginator'] = $paginator;

        return view('feature_sub_categories.index', $datas);
    }
    
    /**
     * 特集サブカテゴリ情報作成.
     */
    public function create()
    {
        return $this->edit(FeatureSubCategory::getDefault());
    }
    
    /**
     * 特集サブカテゴリ情報更新.
     * @param FeatureSubCategory $feature_sub_category 特集サブカテゴリ
     */
    public function edit(FeatureSubCategory $feature_sub_category)
    {
        $datas = $this->getBaseDatas();
        $datas['feature_sub_category'] = $feature_sub_category->only(['id', 'feature_id', 'title', 'url', 'priority']);
        return view('feature_sub_categories.edit', $datas);
    }
    
    /**
     * 特集サブカテゴリ情報保存.
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
                'title' => ['required', 'max:256',],
                'url' => ['nullable'],
                'priority' => ['required', 'integer',],
            ],
            [],
            [
                'id' => 'ID',
                'feature_id' => '特集カテゴリID',
                'title' => 'タイトル',
                'url' => '遷移先URL',
                'priority' => '表示順',
            ]
        );
    
        // 特集サブカテゴリ情報
        $feature_sub_category = null;
        // 初期データ取得
        if ($request->filled('id')) {
            $feature_sub_category = FeatureSubCategory::find($request->input('id'));
        } else {
            $feature_sub_category = FeatureSubCategory::getDefault();
        }

        // 特集サブカテゴリ
        $feature_sub_category->fill($request->only(['feature_id', 'title', 'url', 'priority']));
        
        // トランザクション処理
        $res = DB::transaction(function () use ($feature_sub_category) {
            // 登録実行
            $feature_sub_category->save();
            return true;
        });
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '特集サブカテゴリ情報の編集に失敗しました');
        }
        
        return redirect(route('feature_sub_categories.edit', ['feature_sub_category' => $feature_sub_category]))
            ->with('message', '特集サブカテゴリ情報の編集に成功しました');
    }
}
