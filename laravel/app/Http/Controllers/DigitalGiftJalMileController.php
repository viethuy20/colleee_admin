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
 * デジタルギフトJALMile管理コントローラー.
 */
class DigitalGiftJalMileController extends Controller
{
    /**
     * デジタルギフトJALMile申し込み検索.
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
                $builder = ExchangeRequest::ofDigitalGiftJalMile();
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
        return view('digital_gift_jalmile_index', ['paginator' => $paginator]);
    }

    /**
     * デジタルギフト申し込みURL再送信.
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

        // 交換申し込み再送信
        $url = $exchange_request->digital_gift_url;
        $res = $exchange_request->sendDigitalGiftUrl($url,'JALマイル');

        return redirect()->back()
            ->with('message', $res ? 'デジタルギフトURLの再送信に成功しました' : 'デジタルギフトURLの再送信に失敗しました');

    }
}
