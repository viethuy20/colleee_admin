<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\Mainte;

/**
 * メンテナンス管理コントローラー.
 */
class MaintesController extends Controller
{
    /**
     * メンテナンス一覧.
     */
    public function index()
    {
        $now = Carbon::now()->addDays(-30);
        $mainte_map = Mainte::where('stop_at', '>=', $now)
            ->where('status', '=', 0)
            ->orderBy('type', 'asc')
            ->orderBy('start_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('type')
            ->all();

        return view('maintes.index', ['mainte_map' => $mainte_map,]);
    }

    /**
     * メンテナンス作成.
     * @param int $type 種類
     */
    public function create(int $type)
    {
        return $this->edit(Mainte::getDefault($type));
    }

    /**
     * メンテナンス更新.
     * @param Mainte $mainte メンテナンス
     */
    public function edit(Mainte $mainte)
    {
        // 更新可能ではない場合
        if (!$mainte->editable) {
            abort(404, 'Not Found.');
        }

        $mainte_map = $mainte->only(['id', 'type', 'message',]);
        $mainte_map['start_at'] = $mainte->start_at->format('Y-m-d H:i');
        return view('maintes.edit', ['mainte' => $mainte_map]);
    }

    /**
     * ポイント情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $type_keys = array_keys(config('mainte.type'));

        //
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer',],
                'type' => ['required', 'integer', 'in:'. implode(',', $type_keys),],
                'message' => ['required',],
                'start_at' => ['required', 'date_format:"Y-m-d H:i"',],
            ],
            [],
            [
                'id' => 'ID',
                'type' => '種類',
                'message' => 'メッセージ',
                'start_at' => '開始日時',
            ]
        );

        // メンテナンス
        $mainte = null;
        if ($request->filled('id')) {
            // メンテナンス取得
            $mainte = Mainte::findOrFail($request->input('id'));
        } else {
            // メンテナンスを取得
            $mainte = Mainte::getDefault($request->input('type'));
        }

        // 更新可能ではない場合
        if (!$mainte->editable) {
            abort(404, 'Not Found.');
        }

        //
        $mainte->fill($request->only(['message',]));
        // 開始日時
        $mainte->start_at = Carbon::parse($request->input('start_at').':00');
        
        // トランザクション処理
        $res = DB::transaction(function () use ($mainte) {
            // 保存実行
            $mainte->save();
            return true;
        });

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'メンテナンスの編集に失敗しました');
        }

        return redirect(route('maintes.index'))
            ->with('message', 'メンテナンスの編集に成功しました');
    }

    /**
     * メンテナンス解除.
     * @param Mainte $mainte メンテナンス
     */
    public function destroy(Mainte $mainte)
    {
        if ($mainte->editable) {
            // 公開前の場合、無効化
            $mainte->status = 1;
        } else {
            // 公開済みの場合、終了
            $mainte->stop_at = Carbon::now();
        }
        
        // トランザクション処理
        $res = DB::transaction(function () use ($mainte) {
            // 保存実行
            $mainte->save();
            return true;
        });
        
        return redirect()
            ->back()
            ->withInput()
            ->with('message', $res ? 'メンテナンスの解除に成功しました' : 'メンテナンスの解除１に失敗しました');
    }
}
