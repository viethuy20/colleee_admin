<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Label;
use App\Tag;

class LabelsController extends Controller
{
    /**
     * ラベル検索.
     */
    public function getList(int $type)
    {
        //子ラベル以外を取得
        $builder = Label::select('labels.*')->where('label_id', '=', 0);

        // タイプ検索
        $builder = isset($type) && $type != 0 ? $builder->where('type', '=', $type) : $builder;

        // ラベルリスト取得
        $label_list = $builder->get();
        // タグ候補
        $high_use_tag_list = Tag::orderBy('program_total', 'desc')
            ->limit(500)
            ->pluck('name')
            ->all();

        return view('labels.list', ['label_list' => $label_list, 'type' => $type,
            'high_use_tag_list' => $high_use_tag_list]);
    }

    private function changeStatus(Label $label, bool $enable)
    {
        if ($enable) {
            $action = '公開';
            $label->status = 0;
        } else {
            $action = '非公開';
            $label->status = 1;
            $label->deleted_at = Carbon::now();
        }

        // トランザクション処理
        $res = DB::transaction(function () use ($label) {
            // 登録実行
            $label->save();
            return true;
        });

        // 失敗した場合
        $message = empty($res) ? 'ラベル情報の'.$action.'に失敗しました' : 'ラベル情報の'.$action.'に成功しました';

        return redirect()
            ->back()
            ->with('message', $message);
    }

    /**
     * ラベル公開.
     * @param Label $label ラベル
     */
    public function enable(Label $label)
    {
        return $this->changeStatus($label, true);
    }

    /**
     * ラベル非公開.
     * @param Label $label ラベル
     */
    public function destroy(Label $label)
    {
        return $this->changeStatus($label, false);
    }

    /**
     * ラベル更新.
     * @param Label $label ラベル
     */
    public function edit(Label $label)
    {
        // タグ候補
        $high_use_tag_list = Tag::orderBy('program_total', 'desc')
            ->limit(500)
            ->pluck('name')
            ->all();
        return view('labels.edit', ['target_label' => $label, 'high_use_tag_list' => $high_use_tag_list]);
    }

    /**
     * ラベル情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'id' => ['required', 'integer'],
                'name' => ['required', 'max:30'],
            ],
            [],
            [
                'id' => 'ID',
                'name' => '名称',
            ]
        );

        // ラベル情報取得
        $label = Label::find($request->input('id'));
        // ラベル情報保存
        $label->name = $request->input('name');
        $label->tags = $request->input('tags');
        $label->save();

        return redirect(route('labels.edit', ['label' => $label]))
            ->with('message', 'ラベル情報の編集に成功しました');
    }
}
