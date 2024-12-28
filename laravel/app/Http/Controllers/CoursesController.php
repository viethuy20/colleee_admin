<?php

namespace App\Http\Controllers;

use App\Course;
use App\Program;
use App\ProgramSchedule;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoursesController extends Controller
{
    /**
     * コース情報作成.
     * @param Program $program プログラム情報
     */
    public function create(Program $program)
    {
        return $this->edit($program->default_course);
    }

    public function edit(Course $course)
    {
        $program = $course->program; 
        $datas = ['program' => $program, 'course' => $course];

        if (!isset($course->id))
        {
            // ポイント初期値・入力値を取得
            $datas['point_list'] = [$course->default_point->only(['id', 'fee', 'all_back', 'fee_type', 'bonus',])];
            // プログラム予定初期値・入力値を取得
            $datas['program_schedule']  = [$course->default_schedule->only(['id', 'reward_condition', 'memo',])];
        }

        return view('courses.edit', $datas);
    }
    /**
     * コース情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        // 初期データを取得
        if ($request->filled('id')) {
            $course = Course::findOrFail($request->input('id'));
        } else {
            if (!$request->filled('program_id')) {
                abort(404, 'Not Found.');
            }
            $program = Program::findOrFail($request->input('program_id'));
            $course = $program->default_course;
        }

        $program_id = $request->input('program_id');

        $pointValidatateMsg = [];
        $validateRules = [
            'id' => ['nullable', 'integer',],
            'program_id' => ['nullable', 'integer',],
            'priority' => ['integer','between:1,999',],
        ];
        $validateRules['aff_course_id'] = ['nullable', 'max:256',
            Rule::unique('courses', 'aff_course_id')->where(function ($query) use ($program_id) {
                $query->where('program_id', '=', $program_id);
            }),];
        $validateRules['course_name'] = ['required', 'max:256',
            Rule::unique('courses', 'course_name')->where(function ($query) use ($program_id) {
                $query->where('program_id', '=', $program_id);
            }),];

        if (!$request->filled('id')) 
        {
            $pointValidatateMsg['point'] = 'ポイント';
            $validateRules['point'] = ['required', 'array', 'min:1',];
            foreach ($request->input('point') as $idx => $point) {
                if ($point['fee_type'] == 2) {
                    // 定率の場合
                    $validateRules["point.$idx.fee"] = ['required', 'numeric', 'min:0.1', 'max:100',];
                } else {
                    // 定額の場合
                    $validateRules["point.$idx.fee"] = ['required', 'integer', 'min:1',];
                }
                $validateRules["point.$idx.all_back"] = ['required', 'integer', 'in:0,1',];
                $validateRules["point.$idx.fee_type"] = ['required', 'integer', 'in:1,2',];
                $validateRules["point.$idx.bonus"] = ['required', 'integer', 'in:0,1',];   
                
                $pointValidatateMsg["point.$idx.fee"] = 'ユーザー報酬';
                $pointValidatateMsg["point.$idx.all_back"] = '100%還元';
                $pointValidatateMsg["point.$idx.fee_type"] = '成果タイプ';
                $pointValidatateMsg["point.$idx.bonus"] = 'ボーナス';
            }
            $validateRules['program_schedule.*.reward_condition'] = ['required',];
        }

        $this->validate(
            $request,
            $validateRules,
            [],
            array_merge(
                [
                    'id' => 'ID',
                    'program_id' => 'プログラムID',
                    'aff_course_id' => '連携コースID',
                    'course_name' => 'コース名',
                    'priority' => '表示順',

                    'program_schedule.*.reward_condition' => '獲得条件',
                ],
                $pointValidatateMsg
            )
        );


        $course->fill($request->only(['program_id', 'aff_course_id', 'course_name','priority']));
        if (!$request->filled('id'))
        {
            // 関連初期データ取得
            $associate_data = $request->only(['point', 'program_schedule']);

            // ポイント初期データ取得
            $points = array();
            foreach ($associate_data['point'] as $reqPoint) {
                $point = $program->defaultPoint->fill($reqPoint);
                if ($point->fee_type == 2) {
                    $point->point = null;
                    $point->rate = floatval($reqPoint['fee']) * 0.01;
                    $point->reward_amount_rate = floatval($reqPoint['rewards']) * 0.01;
                    $point->reward_amount = null;
                } else {
                    $point->point = $reqPoint['fee'];
                    $point->rate = null;
                    $point->reward_amount = $reqPoint['rewards'];
                    $point->reward_amount_rate = null;
                }
                $points[] = $point;
            }
            $course->setPoint($points);
            // $program->benefit = count(array_filter($program->point, function ($point) {
            //     return $point->bonus == 1;
            // })) > 0 ? 1 : 0;

            // プログラム予定初期データ登録
            $schedules = array();
            foreach ($associate_data['program_schedule'] as $schedule) {
                $schedules[] = ProgramSchedule::getDefault()->fill($schedule);
            }
            $course->setSchedule($schedules);
        }

        // 保存実行
        $res = $course->saveCourse(Auth::user()->id);
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'コース情報の編集に失敗しました');
        }

        return redirect(route('programs.edit', ['program' => $course->program]).'#course')
            ->with('message', 'コース情報の編集に成功しました');
    }

    /**
     * コース情報公開.
     * @param Course $course {@link Course}
     */
    public function enable(Course $course)
    {
        // 状態を確認
        if (!in_array($course->status, [1], true)) {
            abort(404, 'Not Found.');
        }
        $res = $course->changeStatus(Auth::user()->id, 0);
        // 失敗した場合
        $message = empty($res) ? 'コース情報の公開に失敗しました' : 'コース情報の公開に成功しました';

        return redirect(route('programs.edit', ['program' => $course->program]).'#course')
            ->with('message', $message);
    }

    /**
     * コース情報非公開.
     * @param Course $course {@link Course}
     */
    public function destroy(Course $course)
    {
        $course->changeStatus(Auth::user()->id, 1);
        return redirect(route('programs.edit', ['program' => $course->program]).'#course')
            ->with('message', 'コース情報の非公開に成功しました');
    }
}
