<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use App\External\PayPay;
use App\ExchangeRequest;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\User;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Session;

/**
 * Paypayポイント管理コントローラー.
 */
class PaypayController extends Controller
{
    /**
     * Paypayポイント申し込み検索.
     */
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
                $builder = ExchangeRequest::OfPayPay();
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
            20
        );
        return view('paypay_index', ['paginator' => $paginator, 'message' => $message]);
    }

    //組み戻し処理実行
    public function rollback(int $exchange_request_id)
    {
        $paypay = new PayPay();
        $exchange_request = ExchangeRequest::find($exchange_request_id);
        $res2 = $paypay->checkCashbackDetails($exchange_request_id,$exchange_request->user_id);
        if($res2){
            $status_code = $paypay->getStatusCode();
            $body = $paypay->getBody();
            $data_status_code = $paypay->getDataStatusCode();

            $exchange_request->response_code = $status_code.':'.$data_status_code;
            $exchange_request->response = $body;
            $exchange_request->updated_at = Carbon::now();

            // 正常終了の場合
            if ($data_status_code == 'SUCCESS') {
                $exchange_request->approvalRequest();
                $message = 'ポイント付与が完了しました';
            }elseif($data_status_code == 'ACCEPTED'){
                $exchange_request->paypayGiveCashbackRequest();
                $message = 'ポイント付与が受け付けられました';
            }
            
            return redirect(route('paypay.index',['message' => $message]));
        }else{
            $reverse_res = $paypay->reverseCashback($exchange_request_id,$exchange_request->user_id,$exchange_request->point);
            $res = $exchange_request->rollbackRequest();
            $message = $res ? '組み戻し処理に成功しました' : '組み戻し処理に失敗しました';
            
            return redirect(route('paypay.index',['message' => $message]));
            
        }
        
        
    }

}
