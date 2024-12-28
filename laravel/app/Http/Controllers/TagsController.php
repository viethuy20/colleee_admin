<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\Tag;

/**
 * タグ管理コントローラー.
 */
class TagsController extends Controller
{
    /**
     * タグ検索.
     */
    public function index()
    {
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'sort' => 0, 'name' => null],
            function ($params) {
                $builder = new Tag();
                // タグ名検索
                $builder = isset($params['name']) ? $builder->where('name', '=', $params['name']) : $builder;

                // ソート
                if ($params['sort'] != 0) {
                    $sort_v = ($params['sort'] > 0) ? 'asc' : 'desc';
                    $sort_map = [1 => 'name', 2 => 'program_total', 3 => 'created_at', 4 => 'updated_at'];
                    $builder = $builder->orderBy($sort_map[abs($params['sort'])], $sort_v);
                }
                $builder = $builder->orderBy('id', 'asc');
                return $builder;
            },
            20
        );
        
        return view('tags.index', ['paginator' => $paginator]);
    }
    
    /**
     * タグ情報作成.
     */
    public function create()
    {
        return $this->edit(Tag::getDefault());
    }
    
    /**
     * タグ情報更新.
     * @param Tag $tag タグ
     */
    public function edit(Tag $tag)
    {
        // タグ初期値・入力値を取得
        $tag_map = $tag->only(['id', 'name']);
        return view('tags.edit', ['tag' => $tag_map]);
    }
    
    /**
     * タグ情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $tag_id = $request->input('id');
        
        //
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer'],
                'name' => ['required', 'max:10',
                    Rule::unique('tags', 'name')->where(function ($query) use ($tag_id) {
                        if (isset($tag_id)) {
                            $query->where('id', '<>', $tag_id);
                        }
                    })
                ],
            ],
            [],
            [
                'id' => 'ID',
                'name' => '名称',
            ]
        );
        
        // タグ情報
        $tag = null;
        if ($request->filled('id')) {
            $tag = Tag::find($request->input('id'));
        } else {
            $tag = Tag::getDefault();
        }
        $tag->name = $request->input('name');

        // 保存実行
        $res = $tag->saveTag();
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'タグ情報の編集に失敗しました');
        }

        return redirect(route('tags.edit', ['tag' => $tag]))->with('message', 'タグ情報の編集に成功しました');
    }
}
