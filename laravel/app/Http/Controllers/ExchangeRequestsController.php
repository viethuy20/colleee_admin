<?php
namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\ExchangeRequest;

/**
 * 交換申し込み管理コントローラー.
 */
class ExchangeRequestsController extends Controller
{
     /**
     * インポート.
     * @param Request $request {@link Request}
     */
    public function import(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'status' => ['required', 'integer'],
                'file' => ['required', 'file']
            ],
            [],
            [
                'status' => '状態',
                'file' => 'ファイル',
            ]
        );
        
        $status = $request->input('status');
        $file = $request->file('file');
        
        // ディレクトリ取得
        $dir_path = config('path.user_point');

        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = sprintf("%d%03d%03d.%s", date("YmdHis"), substr(explode(".", (microtime(true) . ""))[1], 0, 3), mt_rand (0, 999),
                $file->getClientOriginalExtension());
            // ファイルが存在しない場合
            if (!file_exists($dir_path.DIRECTORY_SEPARATOR.$file_name)) {
                break;
            }
            $file_name = null;
        }

        // ファイル名が取得できなかった場合
        if (!isset($file_name)) {
            return redirect()->back()->with('message', 'アップロード作業に失敗しました');
        }

        // ファイル移動
        $file->move($dir_path, $file_name);
        $file_path = $dir_path.DIRECTORY_SEPARATOR.$file_name;
        
        // 更新者ID
        $admin_id = Auth::user()->id;

        // バッチ実行
        exe_artisan('exchange_request:import', ['admin_id' => $admin_id, 'status' => $status, 'file' => $file_path]);
        
        $message = '作業を実行中です';
        if ($status == ExchangeRequest::ROLLBACK_STATUS) {
            $message = '組戻し'.$message;
        }
        if ($status == ExchangeRequest::SUCCESS_STATUS) {
            $message = '承認'.$message;
        }
        return redirect()->back()->with('message', $message);
    }
}

