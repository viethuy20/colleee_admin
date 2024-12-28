<?php

namespace App\Http\Controllers;

use App\Paginators\BasePaginator;
use App\Http\Requests\StoreReviewPointRequest;
use App\Http\Requests\UpdateReviewPointRequest;
use App\ReviewPointManagement;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class ReviewPointManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $page_limit = 10; // ページ数

        $paginator = BasePaginator::getDefault(
            [
                'page'       => 1,
                'status'     => null,
            ],
            function ($params) {
                $review_point_management = ReviewPointManagement::query();

                // 状態
                if(isset($params['status'])) {
                    $set_date   = date('Y-m-d H:i:s');

                    switch ($params['status']) {
                        case \App\ReviewPointManagement::STATUS_END :
                            $review_point_management = ReviewPointManagement::where('start_at', '<', $set_date)->where('stop_at', '<', $set_date);
                            break;
                        case \App\ReviewPointManagement::STATUS_START :
                            $review_point_management = ReviewPointManagement::where('start_at', '<=', $set_date)
                                ->where(function ($query) use ($set_date) {
                                    // stop_atがnullの場合（終了日が設定されていない）もしくは終了日の範囲内
                                    $query->whereNull('stop_at')
                                        ->orWhere('stop_at', '>=', $set_date);
                                });
                            break;
                        case \App\ReviewPointManagement::STATUS_STANDBY :
                            $review_point_management =  ReviewPointManagement::where('start_at', '>', $set_date);
                            break;
                        default:
                            break;
                    }
                }
                // ID：降順
                $referral_bonus = $review_point_management->orderBy('id', 'desc');

                return $referral_bonus;
            },
            $page_limit
        );

        return view('review_point_management.index', ['paginator' => $paginator]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('review_point_management.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreReviewPointRequest $request)
    {
        // トランザクション開始
        DB::transaction(function () use ($request) {

            // 新規レコード作成
            $record = new ReviewPointManagement();
            $record->point = $request['rc_point'];
            $startDate = Carbon::parse($request->input('sta'));
            $record->start_at = $startDate;
            $record->save();

            $previousRecord = ReviewPointManagement::whereNull('stop_at')
                    ->where('id', '!=', $record->id)
                    ->first();
            

            // 前のレコードにstop_atに更新する指定日の前日を設定（時系列を揃えるため）
            if ($previousRecord) {
                $previousRecord->stop_at =  Carbon::parse($request->input('sta'))->subDay()->setTime(23, 59, 59);
                $previousRecord->save();
            } 
        });

        return redirect(route('review_point_management.index'))
            ->with('message', '配布ポイントの追加に成功しました');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $referral_bonus = ReviewPointManagement::where('id', '=', $id)->first();

        return view('review_point_management.show', compact('referral_bonus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateReviewPointRequest $request, $id)
    {
        $recordId = $id;

        // トランザクション開始
        DB::transaction(function () use ($recordId, $request) {
            // 更新するレコードを取得
            $record = ReviewPointManagement::find($recordId);

            // 前のレコードを取得
            $previousRecord = ReviewPointManagement::where('stop_at', '<', $record->start_at)
                ->where('id', '!=', $recordId)
                ->orderBy('stop_at', 'desc')
                ->first();

            // 前のレコードにstop_atに更新する指定日の前日を設定（時系列を揃えるため）
            if ($previousRecord) {
                $previousRecord->stop_at =  Carbon::parse($request->input('sta'))->subDay()->setTime(23, 59, 59);
                $previousRecord->save();
            }

            $start_time = Carbon::parse($request['sta'])->startOfDay();
            $record->point = $request['point'];
            $record->start_at = $start_time;
            $record->save();
        });

        return redirect()->route('review_point_management.show', ['review_point_management' => $request['id']])
            ->with('message', '配布ポイントの修正に成功しました');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $recordId = $id;

        // トランザクション開始
        DB::transaction(function () use ($recordId) {
            // 削除するレコードを取得
            $record = ReviewPointManagement::find($recordId);

            // 前のレコードを取得
            $previousRecord = ReviewPointManagement::where('stop_at', '<', $record->start_at)
                ->where('id', '!=', $recordId)
                ->orderBy('stop_at', 'desc')
                ->first();

            // 前のレコードにstop_atに削除レコードの stop_at を設定（時系列を揃えるため）
            if ($previousRecord) {
                $previousRecord->stop_at = $record->stop_at;
                $previousRecord->save();
            }

            // 削除するレコードを削除
            $record->delete();
        });

        return redirect(route('review_point_management.index'))
            ->with('message', '配布ポイントが正常に削除されました');
    }
}
