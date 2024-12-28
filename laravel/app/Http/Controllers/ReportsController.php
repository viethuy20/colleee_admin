<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Report;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\PointMonthlyReport;
use Illuminate\Support\Facades\Validator;


/**
 * レポート管理コントローラー.
 */
class ReportsController extends Controller
{
    /**
     * Hard code value for promotion id.
     */
    private $promotionIds;

    public function __construct() {
        $this->promotionIds = [
            'fanspot' => '8948837',
            'cp'    => '1103874513',
        ];
    }

    /**
     * 一覧.
     * @param int $ym 対象月
     */
    public function getList($ym = null)
    {
        if ($ym != null) {
            if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
                return abort(403, 'This action is unauthorized.');
            }
        }

        // 対象月
        $start_date = isset($ym) ? Carbon::parse(sprintf("%s-%s-01 00:00:00", substr($ym, 0, 4), substr($ym, 4, 2))) :
            Carbon::now()->startOfMonth();
        $end_month = $start_date->copy()->endOfMonth();
        // 交換ポイント数
        $report_list = Report::getReportList($end_month);
        return view('report_list', ['target' => $start_date, 'report_list' => $report_list]);
    }

    public function getMonthly()
    {
        $data = PointMonthlyReport::whereRaw('report_day >= CURDATE() - INTERVAL 12 MONTH')
                    ->get();
        return view('report_month', [
            'data' => $data
        ]);
    }

    public function getCsvMonthly()
    {
        $now = date('YmdHis');
        $fileName = "ポイント推移レポート$now.csv";
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Expires: 0");
        $col = [
            '対象月',
            'ポイント残高',
            '新規発生ポイント高',
            '新規確定ポイント高',
            '交換ポイント高',
            '失効ポイント高'
        ];
        $csv = [$col];
        $data = PointMonthlyReport::whereRaw('report_day >= CURDATE() - INTERVAL 12 MONTH')
                    ->get();
        foreach ($data as $item) {
            $report = [
                date('Y-m-d', strtotime($item->report_day)),
                $item->sum_balance_point,
                $item->sum_action_point,
                $item->sum_confirm_point,
                $item->sum_exchange_point,
                $item->sum_lost_point
            ];
            $csv[] = $report;
        }
        $output = fopen("php://output", "wb");
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($csv as $row)
            fputcsv($output, $row);
        fclose($output);
        exit();
    }

    /**
     * FanSpotレポート
     */
    public function getUserLinkFanspot ()
    {
        $data['start_at'] = \Carbon\Carbon::now()->startOfMonth()->toDateString();
        $data['end_at'] = \Carbon\Carbon::now()->endOfMonth()->toDateString();
        return view('user_link_fanspot', [
            'data' => $data
        ]);
    }

    /**
     * CPレポート
     */
    public function getUserLinkCP()
    {
        $data['start_at'] = \Carbon\Carbon::now()->startOfMonth()->toDateString();
        $data['end_at'] = \Carbon\Carbon::now()->endOfMonth()->toDateString();
        return view('user_link_cp', [
            'data' => $data
        ]);
    }

    public function getCsvUser(Request $request)
    {
        $user_promotion_id = $request->user_promotion_id;
        $type_export = $request->type;
        $end_at = $request->end_at;
        $validator = Validator::make($request->only(
            'start_at', 
            'end_at', 
            'user_promotion_id'
        ), [
            'start_at' => ['required', 'date_format:Y-m-d'],
            'end_at' => ['required', 'date_format:Y-m-d'],
            'user_promotion_id' => ['required', 'numeric'],
        ],
            [
                'start_at.required' => '開始日は必須項目です。',
                'start_at.date_format' => '開始日を正しい日付に修正して下さい。',
                'end_at.required' => '終了日は必須項目です。',
                'end_at.date_format' => '終了日を正しい日付に修正して下さい。',
                'user_promotion_id.required' => 'user_promotion_idは必須項目です。',
                'user_promotion_id.numeric' => 'user_promotion_idは正しくないです。',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (date('Ymd', strtotime($request->start_at)) > date('Ymd', strtotime($request->end_at)) ) {
            return redirect()->back()->withErrors('終了日には開始日以降の日付を指定して下さい。')->withInput();
        }
        if (!in_array($user_promotion_id, $this->promotionIds)) {
            return redirect()->back()->withErrors('user_promotion_idは正しくないです。')->withInput();
        }

        $start_at = $request->start_at.' 00:00:00';
        $end_at = $end_at.' 23:59:59';
        $date_start = date('Ymd', strtotime($request->start_at));
        $date_end = date('Ymd', strtotime($request->end_at));
        $fileNameExportUser = "users_$date_start-$date_end.csv";
        $fileNameExportUserLogin = "user_logins_$date_start".'_'."$date_end.csv";
        $fileNameExportUserPoint = "user_points_$date_start".'_'."$date_end.csv";

        if ($type_export == 1) {
            $this->exportUser($start_at, $end_at, $user_promotion_id, $fileNameExportUser);
        } else if ($type_export == 2) {
            $this->exportUserLogin($start_at, $end_at, $user_promotion_id, $fileNameExportUserLogin);
        } else {
            $this->exportUserPoint($start_at,$end_at, $user_promotion_id, $fileNameExportUserPoint);
        }
    }

    public function exportUser($start_at, $end_at, $user_promotion_id, $fileName)
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Expires: 0");
        $col = [
            'id',
            'old_id',
            'sex',
            'nickname',
            'birthday',
            'prefecture_id',
            'email_status',
            'friend_code',
            'friend_user_id',
            'blog',
            'email_magazine',
            'email_point',
            'ticketed_at',
            'promotion_id',
            'sp',
            'entry_bonus',
            'actioned_at',
            'ip',
            'status',
            'test',
            'q1',
            'q2',
            'updated_admin_id',
            'memo',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
        $csv = [$col];
        $data = DB::table('users')
            ->select('id',
                'old_id',
                'sex',
                'nickname',
                'birthday',
                'prefecture_id',
                'email_status',
                'friend_code',
                'friend_user_id',
                'blog',
                'email_magazine',
                'email_point',
                'ticketed_at',
                'promotion_id',
                'sp',
                'entry_bonus',
                'actioned_at',
                'ip',
                'status',
                'test',
                'q1',
                'q2',
                'updated_admin_id',
                'memo',
                'created_at',
                'updated_at',
                'deleted_at')
            ->where('promotion_id', 'like', "{$user_promotion_id}%")
            ->whereBetween('created_at', [$start_at, $end_at])->get();
        foreach ($data as $item) {
            $report = [
                $item->id,
                $item->old_id,
                $item->sex,
                $item->nickname,
                $item->birthday,
                $item->prefecture_id,
                $item->email_status,
                $item->friend_code,
                $item->friend_user_id,
                $item->blog,
                $item->email_magazine,
                $item->email_point,
                $item->ticketed_at,
                $item->promotion_id,
                $item->sp,
                $item->entry_bonus,
                $item->actioned_at,
                $item->ip,
                $item->status,
                $item->test,
                $item->q1,
                $item->q2,
                $item->updated_admin_id,
                $item->memo,
                $item->created_at,
                $item->updated_at,
                $item->deleted_at
            ];
            $csv[] = $report;
        }
        $output = fopen("php://output", "wb");
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($csv as $row)
            fputcsv($output, $row);
        fclose($output);
        exit();
    }

    public function exportUserLogin($start_at, $end_at, $user_promotion_id, $fileName)
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Expires: 0");
        $col = [
            'id',
            'created_at',
            'user_id',
            'ip',
            'ua',
            'device_id'
        ];
        $csv = [$col];
        $data = DB::table('user_logins')
            ->select('user_logins.id',
                'user_logins.created_at',
                'user_logins.user_id',
                'user_logins.ip',
                'user_logins.ua',
                'user_logins.device_id')
            ->join('users', function($join) use ($user_promotion_id) {
                $join->on('users.id', '=', 'user_logins.user_id')
                    ->where('users.promotion_id', 'like', "{$user_promotion_id}%");
            })
            ->whereBetween('user_logins.created_at', [$start_at, $end_at])->get();
        foreach ($data as $item) {
            $report = [
                $item->id,
                $item->created_at,
                $item->user_id,
                $item->ip,
                $item->ua,
                $item->device_id,
            ];
            $csv[] = $report;
        }
        $output = fopen("php://output", "wb");
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($csv as $row)
            fputcsv($output, $row);
        fclose($output);
        exit();
    }

    public function exportUserPoint($start_at, $end_at, $user_promotion_id, $fileName)
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Expires: 0");
        $col = [
            'id',
            'user_id',
            'point',
            'exchanged_point',
            'diff_point',
            'bonus_point',
            'type',
            'parent_id',
            'title',
            'admin_id',
            'created_at',
            'updated_at'
        ];
        $csv = [$col];
        $data = DB::table('user_points')
            ->select(
                'user_points.id',
                'user_points.user_id',
                'user_points.point',
                'user_points.exchanged_point',
                'user_points.diff_point',
                'user_points.bonus_point',
                'user_points.type',
                'user_points.parent_id',
                'user_points.title',
                'user_points.admin_id',
                'user_points.created_at',
                'user_points.updated_at'
            )
            ->join('users', function($join) use ($user_promotion_id) {
                $join->on('users.id', '=', 'user_points.user_id')
                    ->where('users.promotion_id', 'like', "{$user_promotion_id}%");
            })
            ->whereBetween('user_points.created_at', [$start_at, $end_at])->get();
        foreach ($data as $item) {
            $report = [
                $item->id,
                $item->user_id,
                $item->point,
                $item->exchanged_point,
                $item->diff_point,
                $item->bonus_point,
                $item->type,
                $item->parent_id,
                $item->title,
                $item->admin_id,
                $item->created_at,
                $item->updated_at,
            ];
            $csv[] = $report;
        }
        $output = fopen("php://output", "wb");
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($csv as $row)
            fputcsv($output, $row);
        fclose($output);
        exit();
    }
}
