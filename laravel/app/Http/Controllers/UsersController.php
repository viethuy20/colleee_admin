<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\AffAccount;
use App\AffReward;
use App\EmailToken;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\User;
use App\UserLogin;
use App\UserPoint;

/**
 * ユーザー管理コントローラー.
 */
class UsersController extends Controller
{
    private function getBuilder(array $params)
    {
        $builder = User::select('users.*');
        // ユーザーID検索
        if (isset($params['user_name'])) {
            $user_id = User::getIdByName($params['user_name']);
            $builder = (!empty($user_id)) ? $builder->where('id', '=', $user_id) :
                $builder->where(DB::raw('1 = 0'));
        }
        // メールアドレス検索
        $builder = isset($params['email']) ? $builder->ofEmail($params['email']) : $builder;
        // 状態
        $builder = isset($params['status']) ? $builder->where('status', '=', $params['status']) : $builder;
        // 友達コード検索
        $builder = isset($params['friend_code']) ?
            $builder->where('friend_code', '=', $params['friend_code']) : $builder;
        // 友達ユーザー検索
        if (isset($params['friend_user_name'])) {
            $friend_user_id = User::getIdByName($params['friend_user_name']);
            $builder = (!empty($friend_user_id)) ? $builder->where('friend_user_id', '=', $friend_user_id) :
                $builder->where(DB::raw('1 = 0'));
        }
        // 電話番号検索
        $builder = isset($params['tel']) ? $builder->where('tel', '=', $params['tel']) : $builder;
        // IPアドレス検索
        if (isset($params['ip'])) {
            $builder = $builder->whereIn('id', function ($query) use ($params) {
                $query->select('user_id')
                    ->from('user_logins')
                    ->where('ip', '=', $params['ip']);
            });
        }
        // Fancrewユーザー検索
        if (isset($params['fancrew_user_id'])) {
            $builder = $builder->whereIn('id', function ($query) use ($params) {
                $query->select('user_id')
                    ->from('aff_accounts')
                    ->where('type', '=', AffAccount::FANCREW_TYPE)
                    ->where('number', '=', $params['fancrew_user_id']);
            });
        }
        // 入会期間
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
     * ユーザー一覧.
     */
    public function index()
    {
        if (Auth::user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'user_name' => null, 'email' => null, 'status' => null, 'friend_code' => null,
                'friend_user_name' => null, 'tel' => null, 'ip' => null, 'fancrew_user_id' => null,
                'start_created_at' => null, 'end_created_at' => null,
            ],
            function ($params) {
                return $this->getBuilder($params);
            },
            20
        );

        return view('users.index', ['paginator' => $paginator]);
    }

    public function export()
    {
        // ユーザーリスト取得
        $user_list = $this->getBuilder(request()->all())->get();

        // ポイント数取得
        $user_id_list = $user_list->pluck('id')->all();
        $user_point_map = UserPoint::getUserPointMap($user_id_list);

        // 状態
        $status_map = config('map.user_status');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_'.Carbon::now()->format('YmdHis').'.csv"',
        ];

        return response()->stream(function () use ($user_list, $user_point_map, $status_map) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ユーザーID', '状態', '紹介元ユーザーID', '登録日', 'アクション日', '現在のポイント', '交換累計ポイント',]);
            foreach ($user_list as $user) {
                fputcsv($stream, [$user->name, $status_map[$user->status], ($user->friend_user_id != 0) ?
                    User::getNameById($user->friend_user_id) : '', $user->created_at->format('Y-m-d H:i:s'),
                    $user->actioned_at->format('Y-m-d H:i:s'), $user_point_map[$user->id]->point ?? 0,
                    $user_point_map[$user->id]->exchanged_point ?? 0,
                ]);
            }
            fclose($stream);
        }, 200, $headers);
    }

    /**
     * 非アクションユーザー削除.
     * @param Request $request {@link Request}
     */
    public function deleteNonactionUsers(Request $request)
    {
        $file = $request->file('file');

        // ディレクトリ取得
        $dir_path = config('path.upload');

        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = sprintf(
                "%d%03d%03d_cuenote_users.%s",
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
            return redirect()
                ->back()
                ->with('message', 'ファイルアップロードに失敗しました');
        }
        $file->move($dir_path, $file_name);
        $file_path = $dir_path.DIRECTORY_SEPARATOR.$file_name;

        // バッチ実行
        exe_artisan('cuenote:backup_delete', ['file' => $file_path]);

        return redirect()
            ->back()
            ->with('message', 'ユーザー情報の削除作業中です');
    }

    /**
     * ユーザー情報更新.
     * @param User $user ユーザー
     */
    public function edit(User $user)
    {
        // ユーザー初期値・入力値を取得
        $user_map = $user->only([
            'id', 'old_id', 'name', 'nickname', 'email', 'sex', 'tel', 'email_magazine',
            'email_status', 'point', 'exchanged_point', 'status', 'rank', 'promotion_id',
            'email_point', 'updated_admin_id', 'friend_code', 'ip', 'memo', 'line_id', 'google_id', 'carriers'
        ]);
        $user_map['birthday'] = $user->birthday->format('Y年m月d日');
        $user_map['created_at'] = $user->created_at->format('Y年m月d日 H:i');
        $user_map['updated_at'] = $user->updated_at->format('Y年m月d日 H:i');
        $user_map['deleted_at'] = isset($user->deleted_at) ? $user->deleted_at->format('Y年m月d日 H:i') : null;
        $user_map['point_expire_at'] = $user->point_expire_at->format('Y年m月d日 H:i');
        $user_map['fancrew_user_id'] = $user->fancrew_user_id;

        $friend_user = $user->friend_user;
        if (isset($friend_user->id)) {
            $user_map['friend_user_name'] = $friend_user->name;
        }

        return view('users.edit', ['user' => $user_map]);
    }

    /**
     * ユーザー情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'id' => ['required', 'integer',],
                'email_magazine' => ['required', 'integer', 'in:0,1',],
                'status' => ['required', 'integer',],
            ],
            [],
            [
                'id' => 'ID',
                'email_magazine' => 'メルマガ受信設定',
                'status' => '会員ステータス',
            ]
        );

        // ユーザー情報
        $user = User::find($request->input('id'));
        // ユーザー
        $user->status = $request->input('status');
        $user->email_magazine = $request->input('email_magazine');
        $user->memo = $request->input('memo');
        $user->updated_admin_id = Auth::user()->id;

        $res = DB::transaction(function () use ($user) {
            // 保存
            $user->save();
            return true;
        });

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'ユーザー情報の編集に失敗しました');
        }

        return redirect(route('users.edit', ['user' => $user]))->with('message', 'ユーザー情報の編集に成功しました');
    }

    /**
     * ポイント履歴.
     * @param User $user ユーザー
     */
    public function pointHistory(User $user)
    {
        $paginator = BasePaginator::getDefault(
            ['page' => 1,],
            function ($params) use ($user) {
                $builder = $user->points()->orderBy('id', 'desc');
                return $builder;
            },
            200
        );
        return view('users.point_history', ['paginator' => $paginator, 'user' => $user]);
    }

    /**
     * ログイン履歴.
     * @param User $user ユーザー
     */
    public function loginHistory(User $user)
    {
        $user_login_list = $user
            ->logins()
            ->orderBy('id', 'desc')
            ->get();
        return view('users.login_history', ['user_login_list' => $user_login_list, 'user' => $user]);
    }

    /**
     * ログイン履歴.
     * @param User $user ユーザー
     */
    public function editHistory(User $user)
    {
        return view('users.edit_history', ['user' => $user]);
    }

    /**
     * 電話番号リセット.
     * @param Request $request {@link Request}
     */
    public function resetTel(Request $request)
    {
        //
        $this->validate(
            $request,
            ['user_id' => ['required', 'integer'],],
            [],
            ['user_id' => 'ユーザーID',]
        );

        $user_id = $request->input('user_id');

        // ユーザー情報取得
        $user = User::where('id', '=', $user_id)
            ->whereIn('status', [User::SELF_WITHDRAWAL_STATUS, User::FORCE_WITHDRAWAL_STATUS,
                User::OPERATION_WITHDRAWAL_STATUS])
            ->first();
        // ユーザー情報が存在しなかった場合
        if (!isset($user->id)) {
            abort(404, 'Not Found.');
        }
        $user->tel = null;
        $res = DB::transaction(function () use ($user) {
            // 保存
            $user->save();
            return true;
        });

        return redirect(route('users.edit', ['user' => $user]))
            ->with('message', $res ? '電話番号のリセットに成功しました' : '電話番号のリセットに失敗しました');
    }

    /**
     * メールリマインダー.
     * @param Request $request {@link Request}
     */
    public function emailReminder(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'user_id' => ['required', 'integer',],
                'email' => ['required', 'confirmed', 'custom_email:1', Rule::unique('users'),],
            ],
            [],
            [
                'user_id' => 'ユーザーID',
                'email' => 'メールアドレス',
            ]
        );

        $user_id = $request->input('user_id');
        $email = email_unquote($request->input('email'));

        // 追加データ取得
        $data = (object)['user_id' => $user_id];
        // メールトークンID取得
        $email_token_id = EmailToken::createToken($email, EmailToken::EMAIL_REMINDER_TYPE, $data);

        $options = ['email_token_id' => $email_token_id];
        $res = true;
        try {
            $mailable = new \App\Mail\Colleee($email, 'email_reminder', $options);
            \Mail::send($mailable);
        } catch (\Exception $e) {
            $res = false;
        }

        return redirect(route('users.edit', ['user' => $user_id]))
            ->with('message', $res ? 'メールリマインダーの送信に成功しました' : 'メールリマインダーの送信に失敗しました');
    }

    /**
     * KPI.
     * @param Request $request {@link Request}
     */
    public function kpi()
    {
        $start_at = Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s');
        $start_year_at = Carbon::now()->firstOfYear()->format('Y-m-d H:i:s');
        $end_at = Carbon::now()->format('Y-m-d H:i:s');
        $firstWeek = Carbon::now()->startOfMonth()->weekOfYear;
        $thisWeek = Carbon::now()->weekOfYear;
        $thisMonth = Carbon::now()->month;

        $kpi = [];
        $kpi_year = [];
        $numberUserLogin = [];
        $weekUserLogin = [];
        $yearUserLogin = [];
        $kpi['login_total'] = UserLogin::getLoginTotal($start_at, $end_at);
        $kpi_year['login_total'] = UserLogin::getLoginTotal($start_year_at, $end_at);

        //get number login for day
        $userLogin = UserLogin::getDayUserLogin($start_at, $end_at);
        for ($i = 1; $i <= Carbon::now()->format('d'); $i++) {
            foreach ($userLogin as $user) {
                if (date('d', strtotime($user->date)) == $i) {
                    array_push($numberUserLogin, $user->number_of_users);
                }
            }
            empty($numberUserLogin[$i - 1]) ? array_push($numberUserLogin, 0) : '';
        }
        $numberUserLogin = json_encode($numberUserLogin); //array to json string conversion

        //get number login for week
        $weekLogin = UserLogin::getWeekUserLogin($start_at, $end_at);
        $itemStart = 0;
        for ($i = $firstWeek; $i <= $firstWeek + 4; $i++) {
            foreach ($weekLogin as $week) {
                if ($week->week == $i) {
                    array_push($weekUserLogin, $week->number_of_users);
                }
            }
            empty($weekUserLogin[$itemStart]) ? array_push($weekUserLogin, 0) : '';
            if ($i == $thisWeek) {
                break;
            }
            $itemStart++;
        }
        $weekUserLogin = json_encode($weekUserLogin); //array to json string conversion

        //get number login for year
        $yearLogin = UserLogin::getYearUserLogin($start_year_at, $end_at);
        for ($i = 1; $i <= $thisMonth; $i++) {
            foreach ($yearLogin as $month) {
                if ($month->month == $i) {
                    array_push($yearUserLogin, $month->number_of_users);
                }
            }
            empty($yearUserLogin[$i - 1]) ? array_push($yearUserLogin, 0) : '';
            if ($i == $thisMonth) {
                break;
            }
        }
        $yearUserLogin = json_encode($yearUserLogin); //array to json string conversion

        $kpi['unique_login_total'] = UserLogin::getUniqueLoginTotal($start_at, $end_at);
        $kpi_year['unique_login_total'] = UserLogin::getUniqueLoginTotal($start_year_at, $end_at);

        // アクション人数
        $kpi['unique_action_total'] = AffReward::getUniqueActionTotal($start_at, $end_at);
        $kpi_year['unique_action_total'] = AffReward::getUniqueActionTotal($start_year_at, $end_at);

        $kpi['created_total'] = User::getCreatedTotal($start_at, $end_at);
        $kpi_year['created_total'] = User::getCreatedTotal($start_year_at, $end_at);
        // 新規入会アクション人数
        $kpi['created_action_total'] = AffReward::getCreatedActionTotal($start_at, $end_at);
        $kpi_year['created_action_total'] = AffReward::getCreatedActionTotal($start_year_at, $end_at);

        $kpi['prohibited_total'] = User::getProhibitedTotal($start_at, $end_at);
        $kpi_year['prohibited_total'] = User::getProhibitedTotal($start_year_at, $end_at);

        $kpi['deleted_total'] = User::getDeletedTotal($start_at, $end_at);
        $kpi_year['deleted_total'] = User::getDeletedTotal($start_year_at, $end_at);

        return view('users.kpi', ['kpi' => $kpi, 'numberUserLogin' => $numberUserLogin,
            'weekUserLogin' => $weekUserLogin, 'kpi_year' => $kpi_year, 'yearUserLogin' => $yearUserLogin]);
    }

    public function getDataGrap(Request $request)
    {
        $yearSelect = $request->get('year_select');
        $monthSelect = $request->get('month_select');
        $itemSelect = $request->get('item_select');
        $typeItem = $request->get('type_item');
        $dataGrapDay = [];
        $dataGrapWeek = [];
        $dataGrapYear = [];
        $dataArray1 = [];
        $dataArray2 = [];

        $thisWeek = Carbon::now()->weekOfYear;
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;
        $start_at = Carbon::parse($yearSelect . '-' . $monthSelect)->firstOfMonth()->format('Y-m-d H:i:s');
        $end_at = Carbon::parse($yearSelect . '-' . $monthSelect)->endOfMonth()->format('Y-m-d H:i:s');
        $end_day = Carbon::parse($yearSelect . '-' . $monthSelect)->endOfMonth()->format('d');
        $firstWeek = Carbon::parse($yearSelect . '-' . $monthSelect)->startOfMonth()->weekOfYear;
        $countWeek = Carbon::parse($yearSelect . '-' . $monthSelect)->firstOfMonth()->diffInWeeks(Carbon::parse($yearSelect . '-' . $monthSelect)->endOfMonth());
        $countWeek = abs((int)$countWeek);
        if (isset($typeItem) && $typeItem == 3 ) {
            $start_at = Carbon::now()->firstOfYear()->format('Y-m-d H:i:s');
            $end_at = Carbon::now()->format('Y-m-d H:i:s');
            if ($thisYear != $yearSelect) {
                $start_at = Carbon::parse($yearSelect . '-' . '01')->firstOfMonth()->format('Y-m-d H:i:s');
                $end_at = Carbon::parse($yearSelect . '-' . '12')->endOfMonth()->format('Y-m-d H:i:s');
            }
        }
        $countMonth = 12;
        if ($monthSelect == $thisMonth && $yearSelect == $thisYear) {
            $end_at = Carbon::now()->format('Y-m-d H:i:s');
            $end_day = Carbon::now()->format('d');
            $firstWeek = Carbon::now()->startOfMonth()->weekOfYear;
            $countMonth = $thisMonth;
        }

        //Get kpi
        $kpi['login_total'] = UserLogin::getLoginTotal($start_at, $end_at);
        $kpi['unique_login_total'] = UserLogin::getUniqueLoginTotal($start_at, $end_at);
        $kpi['unique_action_total'] = AffReward::getUniqueActionTotal($start_at, $end_at);
        $kpi['created_total'] = User::getCreatedTotal($start_at, $end_at);
        $kpi['created_action_total'] = AffReward::getCreatedActionTotal($start_at, $end_at);
        $kpi['prohibited_total'] = User::getProhibitedTotal($start_at, $end_at);
        $kpi['deleted_total'] = User::getDeletedTotal($start_at, $end_at);

        //check select item
        if (!empty($itemSelect)) {
            switch ($itemSelect) {
                case 'login_total' :
                    //get day login for month
                    $userLogin = UserLogin::getDayUserLogin($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($userLogin as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataGrapDay, $user->number_of_users);
                            }
                        }
                        empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    //get week login for month
                    $weekLogin = UserLogin::getWeekUserLogin($start_at, $end_at);
                    $itemStart = 0;
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekLogin as $week) {
                            if ($week->week == $i) {
                                array_push($dataGrapWeek, $week->number_of_users);
                            }
                        }
                        empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    //get number login for year
                    $yearLogin = UserLogin::getYearUserLogin($start_at, $end_at);
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($yearLogin as $month) {
                            if ($month->month == $i) {
                                array_push($dataGrapYear, $month->number_of_users);
                            }
                        }
                        empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'unique_login_total':
                    $uniqueLoginTotals = UserLogin::getDayUniqueUserLogin($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($uniqueLoginTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataGrapDay, $user->number_of_users);
                            }
                        }
                        empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    $weekUniqueLogins = UserLogin::getWeekUniqueUserLogin($start_at, $end_at);
                    $itemStart = 0;
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekUniqueLogins as $week) {
                            if ($week->week == $i) {
                                array_push($dataGrapWeek, $week->number_of_users);
                            }
                        }
                        empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    $yearLUniqueogins = UserLogin::getYearUniqueUserLogin($start_at, $end_at);
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($yearLUniqueogins as $month) {
                            if ($month->month == $i) {
                                array_push($dataGrapYear, $month->number_of_users);
                            }
                        }
                        empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'unique_action_total':
                    $uniqueLoginTotals = AffReward::getDayUniqueActionTotal($start_at, $end_at);
                    $dataGrapDay = $this->getDataDayGrap($uniqueLoginTotals, $end_day);

                    $weekUniqueLogins = AffReward::getWeekUniqueActionTotal($start_at, $end_at);
                    $dataGrapWeek = $this->getDataWeekGrap($weekUniqueLogins, $firstWeek, $countWeek ,$thisWeek);

                    $yearUniqueLogins = AffReward::getYearUniqueActionTotal($start_at, $end_at);
                    $dataGrapYear = $this->getDataMonthGrap($yearUniqueLogins, $countMonth); //array to json string conversion
                    break;

                case 'unique_login_totals':
                    // get day user login for month
                    $uniqueLoginTotal = UserLogin::getDayUniqueUserLogin($start_at, $end_at);
                    $uniqueLoginTotals = AffReward::getDayUniqueActionTotal($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($uniqueLoginTotal as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataArray2, $user->number_of_users);
                            }
                        }
                        foreach ($uniqueLoginTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataArray1, $user->number_of_users);
                            }
                        }
                        if (!empty($dataArray1[$i - 1])) {
                            if (!empty($dataArray2[$i - 1])) {
                                array_push($dataGrapDay, number_format(($dataArray1[$i - 1] * 100 / $dataArray2[$i - 1]), 3));
                            } else {
                                array_push($dataGrapDay, 0);
                                array_push($dataArray2, 0);
                            }
                        } else if (!empty($dataArray2[$i - 1])) {
                            array_push($dataGrapDay, 0);
                            array_push($dataArray1, 0);
                        } else {
                            array_push($dataGrapDay, 0);
                            array_push($dataArray1, 0);
                            array_push($dataArray2, 0);
                        }
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    // get week user login for month
                    $uniqueLoginTotal = UserLogin::getWeekUniqueUserLogin($start_at, $end_at);
                    $uniqueLoginTotals = AffReward::getWeekUniqueActionTotal($start_at, $end_at);
                    $itemStart = 0;
                    $dataArray1 = [];
                    $dataArray2 = [];
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($uniqueLoginTotal as $week) {
                            if ($week->week == $i) {
                                array_push($dataArray2, $week->number_of_users);
                            }
                        }
                        foreach ($uniqueLoginTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataArray1, $week->number_of_users);
                            }
                        }
                        if (!empty($dataArray1[$itemStart])) {
                            if (!empty($dataArray2[$itemStart])) {
                                array_push($dataGrapWeek, number_format(($dataArray1[$itemStart] * 100 / $dataArray2[$itemStart]), 3));
                            } else {
                                array_push($dataGrapWeek, 0);
                                array_push($dataArray2, 0);
                            }
                        } else if (!empty($dataArray2[$itemStart])) {
                            array_push($dataGrapWeek, 0);
                            array_push($dataArray1, 0);
                        } else {
                            array_push($dataGrapWeek, 0);
                            array_push($dataArray1, 0);
                            array_push($dataArray2, 0);
                        }
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    // get month user login for year
                    $uniqueLoginTotal = UserLogin::getYearUniqueUserLogin($start_at, $end_at);
                    $uniqueLoginTotals = AffReward::getYearUniqueActionTotal($start_at, $end_at);
                    $dataArray1 = [];
                    $dataArray2 = [];
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($uniqueLoginTotal as $month) {
                            if ($month->month == $i) {
                                array_push($dataArray2, $month->number_of_users);
                            }
                        }
                        foreach ($uniqueLoginTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataArray1, $month->number_of_users);
                            }
                        }
                        if (!empty($dataArray1[$i - 1])) {
                            if (!empty($dataArray2[$i - 1])) {
                                array_push($dataGrapYear, number_format($dataArray1[$i - 1] * 100 / $dataArray2[$i - 1], 3));
                            } else {
                                array_push($dataGrapYear, 0);
                                array_push($dataArray2, 0);
                            }
                        } else if (!empty($dataArray2[$i - 1])) {
                            array_push($dataGrapYear, 0);
                            array_push($dataArray1, 0);
                        } else {
                            array_push($dataGrapYear, 0);
                            array_push($dataArray1, 0);
                            array_push($dataArray2, 0);
                        }
                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'created_total':
                    $createActionTotals = User::getDayCreatedTotal($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($createActionTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataGrapDay, $user->number_of_users);
                            }
                        }
                        empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    $weekCreateActionTotals = User::getWeekCreatedTotal($start_at, $end_at);
                    $itemStart = 0;
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekCreateActionTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataGrapWeek, $week->number_of_users);
                            }
                        }
                        empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    $monthCreateActionTotals = User::getMonthCreatedTotal($start_at, $end_at);
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($monthCreateActionTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataGrapYear, $month->number_of_users);
                            }
                        }
                        empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'created_action_total':
                    $createActionTotals = AffReward::getDayCreatedActionTotal($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($createActionTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataGrapDay, $user->number_of_users);
                            }
                        }
                        empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    $weekCreateActionTotals = AffReward::getWeekCreatedActionTotal($start_at, $end_at);
                    $itemStart = 0;
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekCreateActionTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataGrapWeek, $week->number_of_users);
                            }
                        }
                        empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    $monthCreateActionTotals = AffReward::getMonthCreatedActionTotal($start_at, $end_at);
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($monthCreateActionTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataGrapYear, $month->number_of_users);
                            }
                        }
                        empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'created_totals':
                    $createTotals = User::getDayCreatedTotal($start_at, $end_at);
                    $createActionTotals = AffReward::getDayCreatedActionTotal($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($createTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataArray2, $user->number_of_users);
                            }
                        }
                        foreach ($createActionTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataArray1, $user->number_of_users);
                            }
                        }
                        if (!empty($dataArray1[$i - 1])) {
                            if (!empty($dataArray2[$i - 1])) {
                                array_push($dataGrapDay, number_format(($dataArray1[$i - 1] * 100 / $dataArray2[$i - 1]), 3));
                            } else {
                                array_push($dataGrapDay, 0);
                                array_push($dataArray2, 0);
                            }
                        } else if (!empty($dataArray2[$i - 1])) {
                            array_push($dataGrapDay, 0);
                            array_push($dataArray1, 0);
                        } else {
                            array_push($dataGrapDay, 0);
                            array_push($dataArray1, 0);
                            array_push($dataArray2, 0);
                        }
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    $weekCreateTotals = User::getWeekCreatedTotal($start_at, $end_at);
                    $weekCreateActionTotals = AffReward::getWeekCreatedActionTotal($start_at, $end_at);
                    $itemStart = 0;
                    $dataArray1 = [];
                    $dataArray2 = [];
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekCreateTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataArray2, $week->number_of_users);
                            }
                        }
                        foreach ($weekCreateActionTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataArray1, $week->number_of_users);
                            }
                        }
                        if (!empty($dataArray1[$itemStart])) {
                            if (!empty($dataArray2[$itemStart])) {
                                array_push($dataGrapWeek, number_format(($dataArray1[$itemStart] * 100 / $dataArray2[$itemStart]), 3));
                            } else {
                                array_push($dataGrapWeek, 0);
                                array_push($dataArray2, 0);
                            }
                        } else if (!empty($dataArray2[$itemStart])) {
                            array_push($dataGrapWeek, 0);
                            array_push($dataArray1, 0);
                        } else {
                            array_push($dataGrapWeek, 0);
                            array_push($dataArray1, 0);
                            array_push($dataArray2, 0);
                        }
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    $monthCreateTotals = User::getMonthCreatedTotal($start_at, $end_at);
                    $monthCreateActionTotals = AffReward::getMonthCreatedActionTotal($start_at, $end_at);
                    $dataArray1 = [];
                    $dataArray2 = [];
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($monthCreateTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataArray2, $month->number_of_users);
                            }
                        }
                        foreach ($monthCreateActionTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataArray1, $month->number_of_users);
                            }
                        }
                        if (!empty($dataArray1[$i - 1])) {
                            if (!empty($dataArray2[$i - 1])) {
                                array_push($dataGrapYear, number_format($dataArray1[$i - 1] * 100 / $dataArray2[$i - 1], 3));
                            } else {
                                array_push($dataGrapYear, 0);
                                array_push($dataArray2, 0);
                            }
                        } else if (!empty($dataArray2[$i - 1])) {
                            array_push($dataGrapYear, 0);
                            array_push($dataArray1, 0);
                        } else {
                            array_push($dataGrapYear, 0);
                            array_push($dataArray1, 0);
                            array_push($dataArray2, 0);
                        }
                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'prohibited_total':
                    $prohibitedTotals = User::getDayProhibitedTotal($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($prohibitedTotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataGrapDay, $user->number_of_users);
                            }
                        }
                        empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    $weekProhibitedTotals = User::getWeekProhibitedTotal($start_at, $end_at);
                    $itemStart = 0;
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekProhibitedTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataGrapWeek, $week->number_of_users);
                            }
                        }
                        empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    $monthProhibitedTotals = User::getMonthProhibitedTotal($start_at, $end_at);
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($monthProhibitedTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataGrapYear, $month->number_of_users);
                            }
                        }
                        empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                case 'deleted_total':
                    $createDeletedotals = User::getDayDeletedTotal($start_at, $end_at);
                    for ($i = 1; $i <= $end_day; $i++) {
                        foreach ($createDeletedotals as $user) {
                            if (date('d', strtotime($user->date)) == $i) {
                                array_push($dataGrapDay, $user->number_of_users);
                            }
                        }
                        empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
                    }
                    $dataGrapDay = json_encode($dataGrapDay); //array to json string conversion

                    $weekCreateDeletedTotals = User::getWeekDeletedTotal($start_at, $end_at);
                    $itemStart = 0;
                    for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
                        foreach ($weekCreateDeletedTotals as $week) {
                            if ($week->week == $i) {
                                array_push($dataGrapWeek, $week->number_of_users);
                            }
                        }
                        empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
                        if ($i == $thisWeek) {
                            break;
                        }
                        $itemStart++;
                    }
                    $dataGrapWeek = json_encode($dataGrapWeek); //array to json string conversion

                    $monthCreateDeletedTotals = User::getMonthDeletedTotal($start_at, $end_at);
                    for ($i = 1; $i <= $countMonth; $i++) {
                        foreach ($monthCreateDeletedTotals as $month) {
                            if ($month->month == $i) {
                                array_push($dataGrapYear, $month->number_of_users);
                            }
                        }
                        empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

                        if ($i == $countMonth) {
                            break;
                        }
                    }
                    $dataGrapYear = json_encode($dataGrapYear); //array to json string conversion
                    break;

                default:
                    break;
            }
        }

        return response()->json(['kpi' => $kpi, 'dataGrapDay' => $dataGrapDay,
            'dataGrapWeek' => $dataGrapWeek, 'dataGrapYear' => $dataGrapYear]);
    }

    public function getDataDayGrap($dateDataArrays, $end_day){
        $dataGrapDay = [];
        for ($i = 1; $i <= $end_day; $i++) {
            foreach ($dateDataArrays as $user) {
                if (date('d', strtotime($user->date)) == $i) {
                    array_push($dataGrapDay, $user->number_of_users);
                }
            }
            empty($dataGrapDay[$i - 1]) ? array_push($dataGrapDay, 0) : '';
        }
        return  json_encode($dataGrapDay); //array to json string conversion
    }

    public function getDataWeekGrap($weekDataArrays, $firstWeek, $countWeek ,$thisWeek){
        $itemStart = 0;
        $dataGrapWeek = [];
        for ($i = $firstWeek; $i <= $firstWeek + $countWeek; $i++) {
            foreach ($weekDataArrays as $week) {
                if ($week->week == $i) {
                    array_push($dataGrapWeek, $week->number_of_users);
                }
            }
            empty($dataGrapWeek[$itemStart]) ? array_push($dataGrapWeek, 0) : '';
            if ($i == $thisWeek) {
                break;
            }
            $itemStart++;
        }
        return json_encode($dataGrapWeek); //array to json string conversion
    }

    public function getDataMonthGrap($yearDataArrays, $countMonth){
        $dataGrapYear = [];
        for ($i = 1; $i <= $countMonth; $i++) {
            foreach ($yearDataArrays as $month) {
                if ($month->month == $i) {
                    array_push($dataGrapYear, $month->number_of_users);
                }
            }
            empty($dataGrapYear[$i - 1]) ? array_push($dataGrapYear, 0) : '';

            if ($i == $countMonth) {
                break;
            }
        }
        return json_encode($dataGrapYear); //array to json string conversion
    }
}

