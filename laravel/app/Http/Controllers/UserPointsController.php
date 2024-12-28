<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\UserPoint;

/**
 * ユーザーポイントコントローラー.
 */
class UserPointsController extends Controller
{
    /**
     * プログラムポイントインポート.
     * @param Request $request {@link Request}
     */
    public function importProgram(Request $request)
    {
        //
        $this->validate(
            $request,
            ['file' => 'required|file'],
            [],
            ['file' => 'ファイル',]
        );
        
        $file = $request->file('file');
        
        // ディレクトリ取得
        $dir_path = config('path.user_point');

        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = sprintf("%d%03d%03d_program.%s", date("YmdHis"), substr(explode(".", (microtime(true) . ""))[1], 0, 3), mt_rand (0, 999),
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
            
        // バッチ実行
        exe_artisan('user_point:import_program', ['file' => $file_path]);
        
        return redirect()->back()->with('message', 'プログラム補填CSVのインポート作業を実行中です');
    }
    
    /**
     * 特別プログラムポイントインポート.
     * @param Request $request {@link Request}
     */
    public function importSpProgram(Request $request)
    {
        //
        $this->validate(
            $request,
            ['file' => 'required|file'],
            [],
            ['file' => 'ファイル',]
        );
        
        $file = $request->file('file');
        
        // ディレクトリ取得
        $dir_path = config('path.user_point');

        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = sprintf("%d%03d%03d_sp_program.%s", date("YmdHis"), substr(explode(".", (microtime(true) . ""))[1], 0, 3), mt_rand (0, 999),
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

        // バッチ実行
        exe_artisan('user_point:import_sp_program', ['file' => $file_path]);
        
        return redirect()->back()->with('message', '特別プログラム補填CSVのインポート作業を実行中です');
    }
    
    /**
     * トップ.
     * @param Request $request {@link Request}
     */
    public function index(Request $request)
    {
        // 
        $last_program_user_point = UserPoint::where('type', '=', UserPoint::OLD_PROGRAM_TYPE)
                ->where('parent_id', '<', 0)
                ->orderBy('parent_id', 'asc')
                ->first();
        $last_program_id = ($last_program_user_point->parent_id ?? 0) - 1;
        
        // 
        $last_sp_program_user_point = UserPoint::whereIn('type', [UserPoint::SP_PROGRAM_TYPE, UserPoint::SP_PROGRAM_WITH_REWARD_TYPE])
                ->where('parent_id', '<', 0)
                ->orderBy('parent_id', 'asc')
                ->first();
        $last_sp_program_id = ($last_sp_program_user_point->parent_id ?? 0) - 1;
        
        return view('user_points.index', ['last_program_id' => $last_program_id, 'last_sp_program_id' => $last_sp_program_id]);
    }
}
