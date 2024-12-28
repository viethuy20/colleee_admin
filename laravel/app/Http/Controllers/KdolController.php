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


class KdolController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }

        $message = '';
        if($request->message){
            $message = $request->message;
        }
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'type' => null, 'status' => null, 'user_name' => null, 'number' => null,
                'end_at' => Carbon::now()->copy()->format('Y-m-d'),
            ],
            function ($params) {
                $builder = ExchangeRequest::OfKdol();
                // ID検索
                if (isset($params['number']) && strlen($params['number']) == 20) {
                    $builder = $builder->where('exchange_requests.id', '=', intval(substr($params['number'], 3), 10));
                }
                // ユーザーID検索
                if (isset($params['user_name'])) {
                    $user_id = User::getIdByName($params['user_name']);
                    $builder = (!empty($user_id)) ? $builder->where('exchange_requests.user_id', '=', $user_id) :
                        $builder->where(DB::raw('1 = 0'));
                }
                // 状態検索
                $builder = isset($params['status']) ? $builder->where('exchange_requests.status', '=', $params['status']) : $builder;
                // 申し込み日時
                try {
                    $end_at = Carbon::parse($params['end_at'])->endOfDay();
                    $builder = $builder->where('exchange_requests.created_at', '<=', $end_at);
                } catch (\Exception $e) {
                    $builder = $builder->where(DB::raw('1 = 0'));
                }
                $builder = $builder->orderBy('exchange_requests.id', 'desc');
                return $builder;
            },
        );
        return view('kdol_index', ['paginator' => $paginator, 'message' => $message]);
    }
}