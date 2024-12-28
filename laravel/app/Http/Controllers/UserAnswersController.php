<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\Question;
use App\User;
use App\UserAnswer;
use Illuminate\Support\Facades\Auth;

/**
 * アンケートコメント管理コントローラー.
 */
class UserAnswersController extends Controller
{
    /**
     * アンケート回答検索.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        // ページネーション作成
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'question_id' => null, 'user_name' => null, 'ip' => null],
            function ($params) {
                // 検索条件取得
                $builder = UserAnswer::whereIn('status', [0, 1]);

                // アンケートID検索
                if (isset($params['question_id'])) {
                    $builder = $builder->where('question_id', '=', $params['question_id']);
                }
                // ユーザーID検索
                if (isset($params['user_name'])) {
                    $user_id = User::getIdByName($params['user_name']);
                    $builder = $builder->where('user_id', '=', $user_id);
                }
                // IP検索
                if (isset($params['ip'])) {
                    $builder = $builder->where('ip', '=', $params['ip']);
                }
                $builder = $builder->orderBy('id', 'desc');
                return $builder;
            },
            500
        );
        return view('user_answers.index', ['paginator' => $paginator]);
    }

    /**
     * アンケートコメント情報--更新
     * @param Request $request {@link Request}
     */
    public function changeStatus(Request $request)
    {
        $this->validate(
            $request,
            [
                'status' => ['required', 'integer', 'in:0,1'],
                'id.*' => ['required', 'integer', Rule::exists('user_answers', 'id'),]],
            [],
            [
                'id' => 'コメントID',
                'status' => '状態',
            ]
        );
        
        $status = $request->input('status');
        
        $res = UserAnswer::whereIn('id', $request->input('id'))
            ->where('status', '<>', $status)
            ->update(['status' => $status]);
        // 更新されるレコードがなかった場合はエラー表示
        if ($res == 0) {
            return redirect()->back()->with('message', 'コメント情報の更新に失敗しました');
        }
        return redirect()->back()->with('message', 'コメント情報の更新に成功しました');
    }
}
