<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\ExchangeRequest;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\User;
use Illuminate\Support\Facades\Auth;

/**
 * ギフトコード管理コントローラー.
 */
class GiftCodesController extends Controller
{
    /**
     * ギフトコード申し込み一覧.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'type' => null, 'status' => null, 'user_name' => null, 'number' => null,
                'end_at' => Carbon::now()->copy()->format('Y-m-d'),
            ],
            function ($params) {
                $builder = ExchangeRequest::ofGiftCode();
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
                // 種類検索
                $builder = isset($params['type']) ? $builder->where('type', '=', $params['type']) : $builder;
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

        return view('gift_code_index', ['paginator' => $paginator]);
    }

    /**
     * 交換申し込み再送信.
     * @param int $exchange_request_id 交換申し込みID
     */
    public function resend(int $exchange_request_id)
    {
        // 交換申し込み取得
        $exchange_request = ExchangeRequest::where('id', '=', $exchange_request_id)
            ->where('status', '=', ExchangeRequest::SUCCESS_STATUS)
            ->first();
        if (!isset($exchange_request->id)) {
            return abort(404, 'Not Found.');
        }

        // ギフトコード送信
        $res = $exchange_request->sendGiftCode();

        return redirect()->back()
            ->with('message', $res ? 'ギフトコードの再送信に成功しました' : 'ギフトコードの再送信に失敗しました');
    }
}
