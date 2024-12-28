<?php
namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Illuminate\Http\Request;

use App\Affiriate;
use App\AffReward;
use App\Asp;
use App\External\ColleeeKick;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\User;
use App\AffConfirmReport;
use App\AffActionReport;
use WrapPhp;

/**
 * 成果管理コントローラー.
 */
class AffRewardsController extends Controller
{
    private function getBuilder(array $params)
    {
        $builder = AffReward::select('aff_rewards.*');
        // ユーザーID検索
        if (isset($params['user_name'])) {
            $user_id = User::getIdByName($params['user_name']);
            $builder = (!empty($user_id)) ? $builder->where('user_id', '=', $user_id) :
                $builder->where(DB::raw('1 = 0'));
        }
        // プログラムID
        if (isset($params['program_id'])) {
            $builder = $builder->whereIn('affiriate_id', function ($query) use ($params) {
                $query->select('id')
                    ->from('affiriates')
                    ->where('parent_type', '=', Affiriate::PROGRAM_TYPE)
                    ->where('parent_id', '=', $params['program_id']);
            });
        }
        // ASP
        $builder = isset($params['asp_id']) ? $builder->where('asp_id', '=', $params['asp_id']) : $builder;
        // ASP側広告ID
        $builder = isset($params['asp_affiliate_id']) ?
            $builder->where('asp_affiriate_id', '=', $params['asp_affiliate_id']) : $builder;
        // 注文番号検索
        $builder = isset($params['order_id']) ? $builder->where('order_id', '=', $params['order_id']) : $builder;
        // 状態検索
        $builder = isset($params['status']) ? $builder->where('status', '=', $params['status']) : $builder;
        // エラーコード検索
        $builder = isset($params['code']) ? $builder->where('code', '=', $params['code']) : $builder;

        // 日時
        if (isset($params['start_actioned_at'])) {
            try {
                $start_actioned_at = Carbon::parse($params['start_actioned_at']);
                $builder = $builder->where('actioned_at', '>=', $start_actioned_at);
            } catch (\Exception $e) {
                $builder = $builder->where(DB::raw('1 = 0'));
            }
        }
        if (isset($params['end_actioned_at'])) {
            try {
                $end_actioned_at = Carbon::parse($params['end_actioned_at']);
                $builder = $builder->where('actioned_at', '<=', $end_actioned_at);
            } catch (\Exception $e) {
                $builder = $builder->where(DB::raw('1 = 0'));
            }
        }
        if (isset($params['start_created_at'])) {
            try {
                $start_created_at = Carbon::parse($params['start_created_at']);
                $builder = $builder->where('created_at', '>=', $start_created_at);
            } catch (\Exception $e) {
                $builder = $builder->where(DB::raw('1 = 0'));
            }
        }
        if (isset($params['end_created_at'])) {
            try {
                $end_created_at = Carbon::parse($params['end_created_at']);
                $builder = $builder->where('created_at', '<=', $end_created_at);
            } catch (\Exception $e) {
                $builder = $builder->where(DB::raw('1 = 0'));
            }
        }

        $builder = $builder->orderBy('id', 'desc');

        return $builder;
    }

    /**
     * 成果検索.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'user_name' => null, 'program_id' => null, 'asp_id' => null, 'asp_affiliate_id' => null,
                'order_id' => null, 'status' => null, 'code' => null, 'start_actioned_at' => null,
                'end_actioned_at' => null, 'start_created_at' => null, 'end_created_at' => null,
            ],
            function ($params) {
                return $this->getBuilder($params);
            },
            100
        );

        // ASPマスタ取得
        $asp_map = Asp::where('status', '=', 0)
            ->pluck('name', 'id')
            ->all();
        return view('aff_rewards.index', ['paginator' => $paginator, 'asp_map' => $asp_map]);
    }

    public function export()
    {
        // 成果リスト取得
        $aff_reward_list = $this->getBuilder(request()->all())->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="aff_rewards_'.Carbon::now()->format('YmdHis').'.csv"',
        ];

        return response()->stream(function () use ($aff_reward_list) {
            $stream = fopen('php://output', 'w');
            foreach ($aff_reward_list as $aff_reward) {
                fputcsv($stream, [
                    User::getNameById($aff_reward->user_id), $aff_reward->asp_id,
                    $aff_reward->asp_affiriate_id, $aff_reward->order_id,
                    $aff_reward->actioned_at->format('Y-m-d H:i:s'), $aff_reward->price,
                ]);
            }
            fclose($stream);
        }, 200, $headers);
    }

    /**
     * 成果インポート.
     */
    public function import()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        // ASPマスタ取得
        $asp_map = Asp::where('status', '=', 0)
            ->pluck('name', 'id')
            ->all();
        return view('aff_rewards.import', ['asp_map' => $asp_map]);
    }

    /**
     * プログラム成果CSVインポート.
     * @param Request $request {@link Request}
     */
    public function importProgramCSV(Request $request)
    {
        //
        $this->validate(
            $request,
            ['status' => ['required', 'integer'],
                'file' => ['required', 'file']],
            [],
            ['status' => '状態',
                'file' => 'ファイル',]
        );

        $status = $request->input('status');
        $file = $request->file('file');

        // ディレクトリ取得
        $dir_path = config('path.reward');
        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = sprintf(
                "%d%03d%03d.%s",
                date("YmdHis"),
                substr(explode(".", (microtime(true) . ""))[1], 0, 3),
                mt_rand(0, 999),
                $file->getClientOriginalExtension()
            );
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
        $file->move($dir_path, $file_name);
        $file_path = $dir_path.DIRECTORY_SEPARATOR.$file_name;

        $admin_id = Auth::user()->id;

        $colleee_kick = ColleeeKick::getImportProgram($admin_id, $status, $file_path);

        // 失敗の場合
        if (!$colleee_kick->execute()) {
            return redirect()->back()->with('message', '成果CSVのインポート作業を失敗しました');
        }

        return redirect()->back()->with('message', '成果CSVのインポート作業を完了しました');
    }

    /**
     * プログラムなし成果CSVインポート.
     * @param Request $request {@link Request}
     */
    public function importProgramlessCSV(Request $request)
    {
        //
        $this->validate(
            $request,
            ['file' => ['required', 'file']],
            [],
            ['file' => 'ファイル',]
        );

        $file = $request->file('file');

        // ディレクトリ取得
        $dir_path = config('path.reward');
        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = sprintf(
                "%d%03d%03d.%s",
                date("YmdHis"),
                substr(explode(".", (microtime(true) . ""))[1], 0, 3),
                mt_rand(0, 999),
                $file->getClientOriginalExtension()
            );
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
        $file->move($dir_path, $file_name);
        $file_path = $dir_path.DIRECTORY_SEPARATOR.$file_name;

        $admin_id = Auth::user()->id;

        $colleee_kick = ColleeeKick::getImportProgramless($admin_id, $file_path);

        return redirect()
            ->back()
            ->with('message', $colleee_kick->execute() ?
                '成果CSVのインポート作業を完了しました' : '成果CSVのインポート作業を失敗しました');
    }

    /**
     * @param Request $request
     */
    public function achievement(Request $request)
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $today        = Carbon::today();
        $sixMonthsAgo = Carbon::now()->subMonths(11)->startOfMonth();
        $periods = CarbonPeriod::create($sixMonthsAgo, '1 month', $today);
        $months = [];
        foreach ($periods as $dt) {
            $months[$dt->format("Y-m")] = $dt->format("Y年m月");
        }
        $months = array_reverse($months);
        return view('aff_rewards.achievement', [
            'months' => $months
        ]);
    }

    public function exportAchievement(Request $request)
    {
        $base = $request->get('base');
        $fileName = '';
        $now = date('Ymdhis');
        if ($base == 1) {
            $fileName = "確定ベースレポート_$now.csv";
            $col = [
                'report_day', 
                'asp_id',
                'asp_name',
                'affiriate_id',
                'program_id',
                'program_title',
                'confirm_count',
                'sum_point',
                'point',
                'bonus_point'
            ];
        }
        if ($base == 2) {
            $fileName = "発生ベースレポート_$now.csv";
            $col = [
                'report_day',
                'asp_id',
                'asp_name',
                'affiriate_id',
                'program_id',
                'program_title',
                'click',
                'cv',
                'sum_point',
                'point',
                'bonus_point'
            ];
        }
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Expires: 0");

        $time = $request->get('time', '');
        $time = explode('-', $time);
        $dateStart = date('Y-m-d', strtotime(date($time[0].'-'.$time[1]).' first day of this month'));
        $dateEnd = date('Y-m-d', strtotime(date($time[0].'-'.$time[1]).'last day of this month'));
        $csv = [
            $col
        ];
        if ($base == 1) {
            $rewards = AffConfirmReport::whereBetween('report_day', [$dateStart, $dateEnd])->get();
            if (WrapPhp::count($rewards) > 0) {
                foreach ($rewards as $item) {
                    $data = [
                        date('Y-m-d', strtotime($item->report_day)),
                        $item->asp_id,
                        $item->asp_name,
                        $item->affiriate_id,
                        $item->program_id,
                        $item->program_title,
                        $item->confirm_count,
                        $item->sum_point,
                        $item->point,
                        $item->bonus_point
                    ];
                    $csv[] = $data;
                }
            }
        }
        if ($base == 2) {
            $rewards = AffActionReport::whereBetween('report_day', [$dateStart, $dateEnd])->get();
            if (WrapPhp::count($rewards) > 0) {
                foreach ($rewards as $item) {
                    $data = [
                        date('Y-m-d', strtotime($item->report_day)),
                        $item->asp_id,
                        $item->asp_name,
                        $item->affiriate_id,
                        $item->program_id,
                        $item->program_title,
                        $item->click,
                        $item->cv,
                        $item->sum_point,
                        $item->point,
                        $item->bonus_point
                    ];
                    $csv[] = $data;
                }
            }
        }

        $output = fopen("php://output", "wb");
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($csv as $row)
            fputcsv($output, $row);
        fclose($output);
        exit();
        
    }
}
