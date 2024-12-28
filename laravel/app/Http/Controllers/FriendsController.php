<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Attachment;
use App\Paginators\BasePaginator;
use App\FriendReferralBonusSchedule;
use WrapPhp;

class FriendsController extends Controller
{

    public function index(Request $request)
    {
        $page_limit = 10; // ページ数

        $paginator = BasePaginator::getDefault(
            [
                'page'       => 1,
                'status'     => null,
            ],
            function ($params) {
                // 友達紹介報酬スケジュール
                $friend_referral_bonus = FriendReferralBonusSchedule::Enable();

                // 状態
                if(isset($params['status'])) {
                    $set_date   = date('Y-m-d H:i:s');
                    switch ($params['status']) {
                        case \App\FriendReferralBonusSchedule::STATUS_END :
                            $friend_referral_bonus = $friend_referral_bonus->where('start_at', '<', $set_date)->where('stop_at', '<', $set_date);
                            break;
                        case \App\FriendReferralBonusSchedule::STATUS_START :
                            $friend_referral_bonus = $friend_referral_bonus->where('start_at', '<=', $set_date)->where('stop_at', '>=', $set_date);
                            break;
                        case \App\FriendReferralBonusSchedule::STATUS_STANDBY :
                            $friend_referral_bonus = $friend_referral_bonus->where('start_at', '>', $set_date)->where('stop_at', '>', $set_date);
                            break;
                        default:
                            break;
                    }
                }
                // ID：降順
                $friend_referral_bonus = $friend_referral_bonus->orderBy('id', 'desc');

                return $friend_referral_bonus;
            },
            $page_limit
        );

        // 一定期間内でスケジュールが設定されていない場合は警告を表示する(当月含め)
        $add_month = 12; // 当月含めた月数
        $start_at = Carbon::now()->startOfMonth();
        $end_at   = $start_at->copy()->addMonths($add_month)->addSeconds(-1);
        // 一定期間と重複するスケジュールを取得
        $duplicate_data = FriendReferralBonusSchedule::Enable()->DuplicateDate($start_at, $end_at)->get();

        // 一定期間の年月の配列を作成
        $period = CarbonPeriod::create($start_at, '1 month', $end_at);
        $check_month_list = array();
        foreach ($period as $carbon) {
            $check_month_list[$carbon->format('Y-m')] = false;
        }

        // スケジュールに設定された年月があるか配列に登録する
        $true_month = 0; // スケジュールが設定されている月数をカウント
        foreach ($duplicate_data as $schedule) {
            $period->setStartDate($schedule['start_at']);
            $period->setEndDate($schedule['stop_at']);
            foreach ($period as $schedule_carbon) {
                if(isset($check_month_list[$schedule_carbon->format('Y-m')])) {
                    $check_month_list[$schedule_carbon->format('Y-m')] = true;
                    $true_month++;
                }
            }
        }

        // スケジュールが設定されていない場合、警告する
        $validator = array();
        if ($add_month > $true_month) {
            foreach($check_month_list as $key => $check_month) {
                if($check_month === false ) {
                    $validator[] = $key . 'が未登録です。';
                }
            }
        }

        return view('friends.index', ['paginator' => $paginator])->withErrors($validator);
    }

    public function show(Request $request, int $id)
    {
        $friend_referral_bonus = FriendReferralBonusSchedule::where('id', '=', $id)->first();

        return view('friends.show', compact('friend_referral_bonus'));
    }

    public function newdata()
    {
        return view('friends.show');
    }

    public function update(Request $request)
    {
        // バリデーションクラス
        $validator = Validator::make(
            $request->all(),
            [
                'id'        => ['required', 'integer'],
                'name'      => ['required', 'max:256'],
                'rc_point'  => ['required', 'integer', 'min:0', 'max:9999999'],
                'frb_point' => ['required', 'integer', 'min:0', 'max:9999999'],
                'sta'       => ['required', 'date_format:"Y-m"', 'after:-1 month'],
                'spa'       => ['required', 'date_format:"Y-m"', 'after_or_equal:sta'],
            ],
            [],
            [
                'id'        => 'ID',
                'name'      => '友達紹介報酬スケジュール名',
                'rc_point'  => '獲得条件ポイント',
                'frb_point' => '友達紹介報酬ポイント',
                'sta'       => '紹介掲載開始日時',
                'spa'       => '紹介掲載終了日時',
            ]
        );

        $start_time = date('Y-m-d 00:00:00', strtotime('first day of', strtotime(date($request['sta']))));
        $end_time   = date('Y-m-d 23:59:59', strtotime('last day of',  strtotime(date($request['spa']))));
        $duplicate_data = FriendReferralBonusSchedule::Enable()->NotId($request['id'])->DuplicateDate($start_time, $end_time)->get();
        // 重複する期間がある場合
        if (WrapPhp::count($duplicate_data) > 0) {
            $validator->errors()->add('sta', '紹介掲載期間が重複しています。');
            return redirect()->route('friends.show', ['id'=>$request['id']])->withErrors($validator);
        }
        // 通常のバリデーションエラー
        if ($validator->fails()) {
            return redirect()->route('friends.show', ['id'=>$request['id']])->withErrors($validator);
        }

        $friend_referral_bonus = FriendReferralBonusSchedule::find($request['id']);
        $friend_referral_bonus->name                                 = $request['name'];
        $friend_referral_bonus->reward_condition_point               = $request['rc_point'];
        $friend_referral_bonus->friend_referral_bonus_point          = $request['frb_point'];
        $friend_referral_bonus->start_at                             = $start_time;
        $friend_referral_bonus->stop_at                              = $end_time;
        $friend_referral_bonus->save();

        return redirect()->route('friends.show', ['id'=>$request['id']])
        ->with('message', 'スケジュール修正に成功しました');
    }

    public function create(Request $request)
    {
        // バリデーションクラス
        $validator = Validator::make(
            $request->all(),
            [
                'name'      => ['required', 'max:256'],
                'rc_point'  => ['required', 'integer', 'min:0', 'max:9999999'],
                'frb_point' => ['required', 'integer', 'min:0', 'max:9999999'],
                'sta'       => ['required', 'date_format:"Y-m"', 'after:-1 month'],
                'spa'       => ['required', 'date_format:"Y-m"', 'after_or_equal:sta'],
            ],
            [],
            [
                'name'      => '友達紹介報酬スケジュール名',
                'rc_point'  => '獲得条件ポイント',
                'frb_point' => '友達紹介報酬ポイント',
                'sta'       => '紹介掲載開始日時',
                'spa'       => '紹介掲載終了日時',
            ]
        );

        $start_time = date('Y-m-d 00:00:00', strtotime('first day of', strtotime(date($request['sta']))));
        $end_time   = date('Y-m-d 23:59:59', strtotime('last day of',  strtotime(date($request['spa']))));
        $duplicate_data = FriendReferralBonusSchedule::Enable()->DuplicateDate($start_time, $end_time)->get();
        // 重複する期間がある場合
        if (WrapPhp::count($duplicate_data) > 0) {
            $validator->errors()->add('sta', '紹介掲載期間が重複しています。');
            return redirect()->back()->withInput()->withErrors($validator);
        }
        // 通常のバリデーションエラー
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $friend_referral_bonus = FriendReferralBonusSchedule::create([
            'name'                                 => $request['name'],
            'reward_condition_point'               => $request['rc_point'],
            'friend_referral_bonus_point'          => $request['frb_point'],
            'start_at'                             => $start_time,
            'stop_at'                              => $end_time,
        ]);

        return redirect(route('friends.index'))
        ->with('message', 'スケジュール追加に成功しました');
    }

}
