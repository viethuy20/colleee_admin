<?php
namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\AffAccount;
use App\Http\Controllers\Controller;
use App\User;
use WrapPhp;

/**
 * アフィリエイトアカウント管理コントローラー.
 */
class AffAccountsController extends Controller
{
    public function exportRemovedFancrewList()
    {
        $aff_account_list = AffAccount::where('type', '=', AffAccount::FANCREW_TYPE)
            ->whereNotIn(
                'user_id',
                function ($query) {
                    $query->select('id')->from('users');
                }
            )
            ->get();
            
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' =>
                'attachment; filename="removed_fancrew_users_'.Carbon::now()->format('YmdHis').'.csv"',
        ];

        return response()->stream(function () use ($aff_account_list) {
            $stream = fopen('php://output', 'w');
            foreach ($aff_account_list as $aff_account) {
                fputcsv($stream, [$aff_account->number, User::getNameById($aff_account->user_id),]);
            }
            fclose($stream);
        }, 200, $headers);
    }

    /**
     * Fancrew退会者ユーザー削除.
     * @param Request $request {@link Request}
     */
    public function removedFancrew(Request $request)
    {
        //
        $this->validate(
            $request,
            ['file' => ['required', 'file'],],
            [],
            ['file' => 'ファイル',]
        );

        // 読み込みファイルを作成
        $file = new \App\Csv\SplFileObject($request->file('file')->getRealPath(), 'r');
        $aff_account_number_list = [];
        while (true) {
            // パース
            if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 1 || $data[0] == '') {
                break;
            }
            $aff_account_number_list[] = $data[0];
        }
        // 読み込みファイルをクローズ
        $file = null;

        // 空の場合
        if (empty($aff_account_number_list)) {
            return redirect()->back()->with('message', 'Fancrew退会者ユーザーID削除作業を失敗しました');
        }

        // 確認
        $invalid_total = AffAccount::where('type', '=', AffAccount::FANCREW_TYPE)
            ->whereIn('number', $aff_account_number_list)
            ->whereIn(
                'user_id',
                function ($query) {
                    $query->select('id')->from('users');
                }
            )
            ->count();

        // 削除不可のIDが含まれている場合
        if ($invalid_total > 0) {
            return redirect()->back()->with('message', 'Fancrew退会者ユーザーID削除作業を失敗しました');
        }

        // ユーザー情報を削除
        AffAccount::where('type', '=', AffAccount::FANCREW_TYPE)
            ->whereIn('number', $aff_account_number_list)
            ->delete();

        return redirect()->back()->with('message', 'Fancrew退会者ユーザーID削除作業を完了しました');
    }
}
