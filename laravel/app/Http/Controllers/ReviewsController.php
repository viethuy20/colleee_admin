<?php
namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\Review;
use App\User;

/**
 * 口コミ管理コントローラー.
 */
class ReviewsController extends Controller
{
    /**
     * 口コミ検索.
     * @param int $status 状態
     */
    public function getList(int $status = -1)
    {
        // ページネーション作成
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'program_id' => null, 'user_name' => null, 'ip' => null,],
            function ($params) use ($status) {
                // 検索条件取得
                $builder = Review::select('reviews.*');
                if ($status >= 0) {
                    $builder = $builder->where('reviews.status', '=', $status);
                }

                // プログラムID検索
                if (isset($params['program_id'])) {
                    $builder = $builder->where('program_id', '=', $params['program_id']);
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
                $builder->orderBy('id', 'desc');
                return $builder;
            },
            20
        );

        return view('reviews.list', ['paginator' => $paginator, 'target_status' => $status]);
    }

    /**
     * 口コミ情報更新.
     * @param Request $request {@link Request}
     */
    public function changeStatus(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'status' => ['required', 'integer', 'in:0,1'],
                'id.*' => ['required', 'integer', Rule::exists('reviews', 'id'),]],
            [],
            [
                'id' => 'レビューID',
                'status' => '状態',
            ]
        );
        
        $status = $request->input('status');
        $review_id_list = $request->input('id');
        foreach ($review_id_list as $review_id) {
            $review = Review::find($review_id);
            if (!isset($review->id)) {
                abort(404, 'Not Found.');
            }
            
            // 更新実行
            $res = $review->changeStatus($status);

            // 失敗した場合
            if (empty($res)) {
                return redirect(route('reviews.index'))->withInput()->with('message', '口コミ情報の更新に失敗しました');
            }
        }

        return redirect(route('reviews.index'))->with('message', '口コミ情報の更新に成功しました');
    }
}
