<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\BankAccount;
use App\ExchangeRequest;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\User;
use Illuminate\Support\Facades\Auth;

/**
 * 金融機関管理コントローラー.
 */
class BanksController extends Controller
{
    /**
     * 銀行インポート.
     * @param Request $request {@link Request}
     */
    public function import(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'file' => ['required', 'array'],
                'file.*' => ['required', 'file'],
                'encode' => ['required', 'in:shift-jis,utf-8,euc-jp'],
            ],
            [],
            [
                'file' => 'ファイル',
                'encode' => 'エンコード',
            ]
        );
        
        $file_path_list = [];
        $file_list = $request->file('file');
        foreach ($file_list as $file) {
            // ディレクトリ取得
            $dir_path = config('path.bank');

            for ($i = 0; $i < 3; ++$i) {
                // ファイル名作成
                $file_name = sprintf(
                    "%d%03d%03d.%s",
                    date("YmdHis"),
                    substr(explode(".", (microtime(true) . ""))[1], 0, 3),
                    mt_rand(0, 999),
                    $file->getClientOriginalExtension()
                );
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
            $file_path_list[] = $dir_path.DIRECTORY_SEPARATOR.$file_name;
        }

        if (empty($file_path_list)) {
            \Log::info('file list');
            return redirect()->back()->with('message', 'アップロード作業に失敗しました');
        }
        
        // バッチ実行
        exe_artisan('bank:import', ['encode' => $request->input('encode'), 'file' => $file_path_list]);
        
        return redirect()->back()->with('message', '銀行CSVのインポート作業を実行中です');
    }
    
    /**
     * 金融機関振込申し込み一覧.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'status' => null, 'user_name' => null, 'number' => null,
                'end_at' => Carbon::now()->copy()->format('Y-m-d'),
            ],
            function ($params) {
                $builder = ExchangeRequest::ofBank();
                // ID検索
                if (isset($params['number']) && strlen($params['number']) == 20) {
                    $builder = $builder->where('id', '=', intval(substr($params['number'], 3), 10));
                }
                // ユーザーID検索
                if (isset($params['user_name'])) {
                    $user_id = User::getIdByName($params['user_name']);
                    $builder = (!empty($user_id)) ? $builder->where('user_id', '=', $user_id) :
                        $builder->where(DB::raw('1 = 0'));
                }
                // 状態検索
                $builder = isset($params['status']) ? $builder->where('status', '=', $params['status']) : $builder;
                // 申し込み日時
                try {
                    $end_at = Carbon::parse($params['end_at'])->endOfDay();
                    $builder = $builder->where('created_at', '<=', $end_at);
                } catch (\Exception $e) {
                    $builder = $builder->where(DB::raw('1 = 0'));
                }

                $builder = $builder->orderBy('id', 'desc');

                return $builder;
            },
            20
        );

        return view('banks.index', ['paginator' => $paginator]);
    }
    
    /**
     * アカウント一覧表示.
     * @param User $user ユーザー
     */
    public function showAccountList(User $user)
    {
        $bank_account_list = $user->bank_accounts()->get();
        return view('banks.account_list', ['user' => $user, 'bank_account_list' => $bank_account_list]);
    }
    
    /**
     * アカウント削除.
     * @param User $user ユーザー
     */
    public function deleteAccount(User $user)
    {
        $user->bank_accounts()
            ->where('status', '=', 0)
            ->update(['status' => 1]);

        return redirect()
            ->back()
            ->with('message', '銀行口座情報のリセットに成功しました。');
    }
}
