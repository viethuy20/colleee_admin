<?php
namespace App\Http\Controllers;

use App\Course;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\Point;
use App\Program;
use App\ProgramSchedule;

/**
 * ポイント管理コントローラー.
 */
class PointsController extends Controller
{
    /**
     * ポイント情報作成.
     * @param Program $program プログラム情報
     */
    public function create(Program $program, Course $course = null)
    {   
        $default_point = $program->multi_course == 1 
            ? $course->default_point : $program->default_point;

        return $this->edit($default_point);
    }

    /**
     * ポイント情報更新.
     * @param Point $point ポイント情報
     */
    public function edit(Point $point)
    {
        $now = Carbon::now();

        if (!empty($point->course_id)) {
            $course = Course::findOrFail($point->course_id);
        }

        // プログラム情報を取得
        $program = $point->program;

        // ポイント情報
        $point_map = $point->only(['id', 'fee', 'all_back', 'fee_type', 'bonus','rewards',
            'editable', 'stopped', 'start_at_editable', 'time_sale_editable']);
        $point_map['time_sale'] = $point->time_sale ? 1 : 0;
        $point_map['today_only'] = $point->today_only ? 1 : 0;
        $point_map['start_at'] = $point->start_at->format('Y-m-d H:i');
        $start_at_list =  explode(' ',$point_map['start_at']);
        $point_map['start_at_date'] = $start_at_list[0];
        $point_map['start_at_time'] = $start_at_list[1];
        $point_map['stop_at'] = $point->stop_at->format('Y-m-d H:i');
        $stop_at_list =  explode(' ',$point_map['stop_at']);
        $point_map['stop_at_date'] = $stop_at_list[0];
        $point_map['stop_at_time'] = $stop_at_list[1];
        if (isset($point->sale_stop_at)) {
            $point_map['sale_stop_at'] = $point->sale_stop_at->format('Y-m-d H:i:s');
            $sale_stop_at_list =  explode(' ',$point_map['sale_stop_at']);
            $point_map['sale_stop_at_date'] = $sale_stop_at_list[0];
            $point_map['sale_stop_at_time'] = $sale_stop_at_list[1];

        }

        // プログラム予定情報を取得
        $scheduleBuilder = isset($course) ? $course->schedules() : $program->schedules();
        $program_schedule_list = $scheduleBuilder
                ->where('start_at', '<=', $point->stop_at->lt($now) ? $point->stop_at : $now)
                ->where('stop_at', '>=', $point->start_at)
                ->orderBy('id', 'asc')
                ->get();

        $program_schedule_map = null;
        // ポイント終了日時を検証
        if (!$point->stopped) {
            $scheduleBuilder = isset($course) ? $course->schedules() : $program->schedules();
            $program_schedule = $scheduleBuilder
                ->where('start_at', '>', $now)
                ->orderBy('id', 'desc')
                ->first();

            if (!isset($program_schedule->id)) {
                $program_schedule = ProgramSchedule::getDefault($program->id, $course->id ?? null);
            }

            $program_schedule_map = $program_schedule->only(['id', 'reward_condition', 'memo', 'admin', 'updated_at']);
            $program_schedule_map['start_at'] = $program_schedule->start_at->format('Y-m-d H:i');
            $start_at_list =  explode(' ',$program_schedule_map['start_at']);
            $program_schedule_map['start_at_date'] = $start_at_list[0];
            $program_schedule_map['start_at_time'] = $start_at_list[1];
        }

        $datas = [
            'program' => $program,
            'point' => $point_map,
            'program_schedule_list' => $program_schedule_list,
            'program_schedule' => $program_schedule_map
        ];

        if (isset($course)) {
            $datas['course'] = $course;
        }

        return view('points.edit', $datas);
    }

    /**
     * ポイント情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        //
        $now = Carbon::now();

        // 初期データ取得
        if ($request->filled('id')) {
            // ポイント情報を取得
            $point = Point::findOrFail($request->input('id'));
        } else {
            // プログラムIDが存在しない場合
            if (!$request->filled('program_id')) {
                abort(404, 'Not Found.');
            }
            // プログラムを取得
            $program = Program::findOrFail($request->input('program_id'));
            // ポイント情報作成
            $point = $program->default_point;
            // コース情報
            if ($request->filled('course_id')) {
                $point->course_id = $request->input('course_id');
            }
        }

        // 直前のポイント
        $prev_point = $point->previous;

        $time_sale = ($request->input('time_sale', $point->time_sale ? 1 : 0) == 1);

        $validateRules = [
            'id' => ['nullable', 'integer',],
            'program_id' => ['nullable', 'integer',],
            'course_id' => ['nullable', 'integer',],
            'all_back' => ['required', 'integer', 'in:0,1',],
            'today_only' => ['required', 'integer', 'in:0,1',],
            'sale_stop_at' => ['nullable', 'date_format:"Y-m-d H:i:s"',],
        ];

        // 更新可能な場合
        if ($point->editable) {
            $validateRules['bonus'] = ['required', 'integer', 'in:0,1',];
            $validateRules['fee_type'] = ['required', 'integer',];

            if ($request->input('fee_type', $point->fee_type) == 2) {
                // 定率の場合
                $validateRules['fee'] = ['required', 'numeric', 'min:0.1', 'max:100',];
                $validateRules['rewards'] = ['nullable', 'numeric', 'min:0.1', 'max:100',];

            } else {
                // 定額の場合
                $validateRules['fee'] = ['required', 'integer', 'min:1',];
                $validateRules['rewards'] = ['nullable', 'integer', 'min:1',];
            }
            // タイムセール前後は成果タイプの変更はできない
            if ((isset($prev_point->id) && $prev_point->time_sale) || $time_sale) {
                $validateRules['fee_type'][] = 'in:'.$prev_point->fee_type;
                if ($time_sale) {
                    // タイムセールの場合、報酬は上がっていなければならない
                    $validateRules['fee'][] = 'gt:'.$prev_point->fee;
                } else {
                    // 前のポイントがタイムセールの場合、報酬は下がっていなければならない
                    $validateRules['fee'][] = 'lt:'.$prev_point->fee;
                }
            } else {
                $validateRules['fee_type'][] = 'in:1,2';
            }
        }

        // 開始日時が更新可能な場合
        if ($point->start_at_editable) {
            $validateRules['start_at_date'] = ['required', 'date_format:"Y-m-d"',];
            $validateRules['start_at_time'] = ['required', 'date_format:"H:i"',];
            $start_at = $request->input('start_at_date').' '.$request->input('start_at_time');
            $request->merge(['start_at' => $start_at]);
            $start_at_min = $point->start_at_min;
            $start_at_max = $point->start_at_max;

            // タイムセールの場合、前のポイントが1日以上公開されていない場合、更新できない
            if ($time_sale) {
                $timesale_start_at = $prev_point->start_at->copy()->addDays(1);
                $start_at_min = $timesale_start_at->gte($start_at_min) ? $timesale_start_at : $start_at_min;
            }

            $validateRules['start_at'][] = 'after_or_equal:'.$start_at_min->format('Y-m-d H:i:s');
            $validateRules['start_at'][] = 'before_or_equal:'.$start_at_max->format('Y-m-d H:i:s');
        }

        // タイムセールが更新可能な場合
        if ($point->time_sale_editable) {
            $validateRules['time_sale'] = ['required', 'integer',];
            // 初回がタイムセール,連続したタイムセールを禁止
            $validateRules['time_sale'][] = (!isset($prev_point->id) || $prev_point->time_sale) ? 'in:0' : 'in:0,1';
        }

        //
        $program_schedule_edit = $request->input('program_schedule.edit', 0) == 1;
        if ($program_schedule_edit) {
            $validateRules['program_schedule.reward_condition'] = 'required';
            $validateRules['program_schedule.start_at_date'] = ['required', 'date_format:"Y-m-d"'];
            $validateRules['program_schedule.start_at_time'] = ['required', 'date_format:"H:i"'];
            $ps_start_at = $request->input('program_schedule.start_at_date').' '.$request->input('program_schedule.start_at_time');
            $request->merge(['ps_start_at' => $ps_start_at]);
            $validateRules['ps_start_at'] = [
                'after:'.$now->format('Y-m-d H:i:s'),];
        }

        //
        $this->validate(
            $request,
            $validateRules,
            [],
            [
                'id' => 'ID',
                'program_id' => 'プログラムID',
                'course_id' => 'コースID',
                'fee' => 'ユーザー報酬',
                'all_back' => '100%還元',
                'time_sale' => 'タイムセール',
                'today_only' => '本日限定',
                'fee_type' => '成果タイプ',
                'bonus' => 'ボーナス',
                'start_at_date' => '開始日',
                'start_at_time' => '開始時',
                'start_at' => '開始日時',
                'sale_stop_at' => '一時停止日時',
                'program_schedule.reward_condition' => '獲得条件',
                'program_schedule.start_at_date' => '獲得条件開始日',
                'program_schedule.start_at_time' => '獲得条件開始時',
                'ps_start_at' => '獲得条件開始日時',
                'rewards' => '報酬額',
            ]
        );

        $point->fill($request->only(['all_back', 'today_only']));
        $r_sale_stop_at = null;
        if ($request->input('sale_stop_at_date') && $request->input('sale_stop_at_time')) {
            $r_sale_stop_at = $request->input('sale_stop_at_date').' '.$request->input('sale_stop_at_time');
        }
        $point->sale_stop_at = isset($r_sale_stop_at) ? Carbon::parse($r_sale_stop_at) : null;
        if ($point->editable) {
            $point->fill($request->only(['fee_type', 'bonus',]));
            if ($point->fee_type == 2) {
                $point->point = null;
                $point->rate = floatval($request->input('fee')) * 0.01;
                $point->reward_amount_rate = floatval($request->input('rewards')) * 0.01;
            } else {
                $point->point = $request->input('fee');
                $point->rate = null;
                $point->reward_amount = $request->input('rewards');

            }
        }
        // タイムセールが更新可能な場合
        if ($point->time_sale_editable) {
            $point->time_sale = $time_sale;
        }

        // 開始日時が更新可能な場合
        if ($point->start_at_editable) {
            $point->start_at = Carbon::parse($start_at.':00');
        }

        //
        $program_schedule = null;
        if ($program_schedule_edit) {
            // プログラム予定情報を取得
            $program_schedule = $request->filled('program_schedule.id') ?
                ProgramSchedule::find($request->input('program_schedule.id')) :
                    ProgramSchedule::getDefault($point->program_id, $point->course_id);

            //
            $program_schedule_map = $request->only(['program_schedule.reward_condition',
                'program_schedule.memo']);
            $program_schedule->fill($program_schedule_map['program_schedule']);

            // マルチコースの紐づけ
            if ($request->filled('course_id')) {
                $program_schedule->course_id = $request->input('course_id');
            }
            if (!is_null($point->course_id)) {
                $program_schedule->course_id = $point->course_id;
            }

            // 開始日時
            $ps_start_at = $request->input('program_schedule.start_at_date').' '.$request->input('program_schedule.start_at_time');
            $program_schedule->start_at = Carbon::parse($ps_start_at.':00');
        }

        $admin_id = Auth::user()->id;
        // トランザクション処理
        $res = DB::transaction(function () use ($admin_id, $point, $program_schedule) {
            // 登録実行
            $point->savePointInner($admin_id);

            // プログラム予定更新
            if (isset($program_schedule)) {
                $program_schedule->saveProgramScheduleInner($admin_id);
                $prev_program_schedule = $program_schedule->program->schedules()
                    ->when(!is_null($point->course_id), function ($query) use ($point) {
                        return $query->where('course_id', '=', $point->course_id);
                    })
                    ->where('id', '<', $program_schedule->id)
                    ->orderBy('id', 'desc')
                    ->first();
                if (isset($prev_program_schedule->id)) {
                    $prev_program_schedule->stop_at = $program_schedule->start_at->copy()->subSeconds(1);
                    $prev_program_schedule->save();
                }
            }
            return true;
        });

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'ポイント情報の編集に失敗しました');
        }

        return redirect(route('programs.edit', ['program' => $point->program]).'#point')
            ->with('message', 'ポイント情報の編集に成功しました');
    }
}
