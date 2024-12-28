<?php
namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Validator;

use App\Asp;
use App\Http\Controllers\Controller;

/**
 * ASP管理コントローラー.
 */
class AspsController extends Controller
{
    /**
     * ASP一覧.
     */
    public function index()
    {
        // ASP一覧を取得
        $asp_list = Asp::orderBy('id', 'asc')
            ->where('status', '=', 0)
            ->get();
        return view('asps.list', ['asp_list' => $asp_list]);
    }

    /**
     * ASP更新.
     * @param Asp $asp ASP
     */
    public function edit(Asp $asp)
    {
        // ASP初期値・入力値を取得
        $asp_map = $asp->only(['id', 'name', 'company', 'allow_ips', 'url', 'url_parameter_name']);
        return view('asps.edit', ['asp' => $asp_map]);
    }

    /**
     * ASP保存.
     */
    public function store(Request $request)
    {
        $validateAttributes = [
            'id' => 'ID',
            'name' => '名称',
            'company' => '企業',
            'allow_ips' => '許可IPアドレス',
            'url' => '遷移先URL',
            'url_parameter_name' => '商品リンクパラメーター名',
        ];
        //
        $this->validate(
            $request,
            [
                'id' => ['required', 'integer'],
                'name' => ['required'],
                'company' => ['required'],
                // 'allow_ips' => ['required'],
                'url' => ['nullable', 'url'],
                'url_parameter_name' => ['nullable']
            ],
            [],
            $validateAttributes
        );

        $allow_ips = preg_replace("/\r\n|\r|\n/", "\n", $request->input('allow_ips'));
        $allow_ip_list = explode("\n", $allow_ips);
        foreach ($allow_ip_list as $allow_ip) {
            // 追加バリデーション
            $validator = Validator::make(
                ['allow_ips' => $allow_ip],
                ['allow_ips' => 'custom_ipv4'],
                ['custom_ipv4' => ':attributeを正しいサブネットにしてください。'],
                $validateAttributes
            );

            //
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        // 初期データ取得
        $asp = Asp::find($request->input('id'));
        $asp->fill($request->only(['name', 'company', 'url', 'url_parameter_name']));

        // レスポンス許可IPアドレスキー名
        $asp->allow_ips = $allow_ips;

        // トランザクション処理
        $res = DB::transaction(function () use ($asp) {
            // 登録実行
            $asp->save();
            return true;
        });
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'ASPの編集に失敗しました');
        }
        return redirect(route('asps.edit', ['asp' => $asp]))->with('message', 'ASPの編集に成功しました');
    }
}
