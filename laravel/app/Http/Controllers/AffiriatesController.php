<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

use App\Affiriate;
use App\Asp;
use App\Program;

/**
 * アフィリエイト管理コントローラー.
 */
class AffiriatesController extends Controller
{
    /**
     * アフィリエイト情報作成.
     * @param Program $program プログラム情報
     */
    public function create(Program $program)
    {
        return $this->edit($program->default_affiriate);
    }

    /**
     * アフィリエイト情報更新.
     * @param Affiriate $affiriate アフィリエイト情報
     */
    public function edit(Affiriate $affiriate)
    {
        $datas = [
            'program' => $affiriate->parent,
            'asp_map' => Asp::where('status', '=', 0)
                ->where('type', '=', ASP::PROGRAM_TYPE)
                ->pluck('name', 'id')
                ->all()
        ];

        $affiriate_map = $affiriate->only(['id', 'parent_id', 'asp_id', 'ad_id',
            'asp_affiriate_id', 'url', 'img_url', 'accept_days', 'give_days', 'memo', 'editable']);
        $affiriate_map['start_at'] = $affiriate->start_at->format('Y-m-d H:i');
        $start_at_list =  explode(' ',$affiriate_map['start_at']);
        $affiriate_map['start_at_date'] = $start_at_list[0];
        $affiriate_map['start_at_time'] = $start_at_list[1];
        $affiriate_map['stop_at'] = $affiriate->stop_at->format('Y-m-d H:i');
        $stop_at_list =  explode(' ',$affiriate_map['stop_at']);
        $affiriate_map['stop_at_date'] = $stop_at_list[0];
        $affiriate_map['stop_at_time'] = $stop_at_list[1];
        $datas['affiriate'] = $affiriate_map;

        return view('affiriates.edit', $datas);
    }

    /**
     * ポイント情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        //
        $now = Carbon::now();
        // アフィリエイト
        $affiriate = null;
        if ($request->filled('id')) {
            // アフィリエイト情報取得
            $affiriate = Affiriate::findOrFail($request->input('id'));
        } else {
            // プログラム情報が存在しない場合
            if (!$request->filled('parent_id')) {
                abort(404, 'Not Found.');
            }
            // プログラム情報を取得
            $program = Program::findOrFail($request->input('parent_id'));
            $affiriate = $program->default_affiriate;
        }

        // 直前のアフィリエイト
        $prev_affiriate = $affiriate->previous;

        $asp_id = $request->input('asp_id');
        $validateRules = [
            'id' => ['nullable', 'integer'],
            'parent_id' => ['required', 'integer'],
            'asp_id' => ['required', 'integer'],
            'asp_affiriate_id' => [
                'required',
                Rule::unique('affiriates')->where(function ($query) use ($affiriate, $asp_id, $now) {
                    if (isset($affiriate->id)) {
                        $query->where('id', '<>', $affiriate->id);
                    }
                    $query->where(function ($query) use ($affiriate, $now) {
                        // 別のプログラムで利用されていないかを確認
                        if (isset($affiriate->parent_id)) {
                            $query->orWhereRaw(sprintf(
                                "!(parent_type = %d and parent_id = %d)",
                                $affiriate->parent_type,
                                $affiriate->parent_id
                            ));
                        }
                        // 現在利用中でないか確認
                        $query->orWhere('stop_at', '>=', $now);
                    });
                    $query->where('asp_id', '=', $asp_id);
                })
            ],
            'ad_id' => ['required',
                Rule::unique('affiriates')->where(function ($query) use ($affiriate, $asp_id, $now) {
                    if (isset($affiriate->id)) {
                        $query->where('id', '<>', $affiriate->id);
                    }
                    $query->where(function ($query) use ($affiriate, $now) {
                        // 別のプログラムで利用されていないかを確認
                        if (isset($affiriate->parent_id)) {
                            $query->orWhereRaw(sprintf(
                                "!(parent_type = %d and parent_id = %d)",
                                $affiriate->parent_type,
                                $affiriate->parent_id
                            ));
                        }
                        // 現在利用中でないか確認
                        $query->orWhere('stop_at', '>=', $now);
                    });
                    $query->where('asp_id', '=', $asp_id);
                })],
            'url' => ['required', 'url', 'secure_resource',
                'regex:/^(?=.*'.preg_quote(Affiriate::COLLEEE_USERID_REPLACE).').*$/'],
            'img_url' => ['required', 'url', 'secure_resource'],
            'accept_days' => ['required', 'integer'],
            'accept_speedy' => $request->input('accept_days') == 0 ? ['required', 'integer', 'in:1'] :
                ['nullable', 'integer', 'in:0'],
            'give_days' => ['nullable', 'integer'],
        ];
        // 更新可能な場合
        if ($affiriate->editable) {
            $validateRules['start_at_date'] =  ['required', 'date_format:"Y-m-d"',];
            $validateRules['start_at_time'] = ['required', 'date_format:"H:i"',];
            $start_at = $request->input('start_at_date').' '.$request->input('start_at_time');
            $prev_time = (isset($prev_affiriate->id) && $prev_affiriate->start_at->gte($now)) ?
                $prev_affiriate->start_at :$now;
            $start_at_rule = ['after:'.$prev_time->format('Y-m-d H:i:s')];
            // 次のアフィリエイト
            $next_affiriate = $affiriate->next;
            if (isset($next_affiriate->start_at)) {
                $start_at_rule[] = 'before:'.$next_affiriate->start_at->format('Y-m-d H:i:s');
            }
            $request->merge(['start_at' => $start_at]);
            $validateRules['start_at'] = $start_at_rule;


        }

        //
        $this->validate(
            $request,
            $validateRules,
            [],
            [
                'id' => 'ID',
                'parent_id' => '親ID',
                'asp_id' => 'ASP',
                'asp_affiriate_id' => 'データ連携ID',
                'ad_id' => 'ASP別検索ID',
                'url' => '遷移先',
                'img_url' => '画像URL',
                'accept_days' => '獲得時期目安',
                'accept_speedy' => '即時承認',
                'give_days' => '予定反映目安',
                'start_at_date' => '開始日',
                'start_at_time' => '開始時',
                'start_at' => '開始日時',
                'memo' => 'Memo',
            ]
        );

        //
        $affiriate->fill($request->only(['parent_id', 'asp_id', 'asp_affiriate_id',
            'ad_id', 'url', 'img_url', 'accept_days', 'give_days', 'memo']));
        if ($affiriate->editable) {
            // 開始日時
            $affiriate->start_at = Carbon::parse($start_at.':00');
        }

        // トランザクション処理
        $res = DB::transaction(function () use ($prev_affiriate, $affiriate) {
            // 前のアフィリエイトの終了日時を設定
            if (isset($prev_affiriate)) {
                $prev_affiriate->stop_at = $affiriate->start_at->copy()->subSeconds(1);
                $prev_affiriate->save();
            }
            // 登録実行
            $affiriate->save();
            return true;
        });

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'アフィリエイト情報の編集に失敗しました');
        }

        return redirect(route('programs.edit', ['program' => $affiriate->parent]).'#affiriate')
            ->with('message', 'アフィリエイト情報の編集に成功しました');
    }
}
