<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
// use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\RecommendProgram;
use App\Paginators\BasePaginator;
use App\Program;
/**
 * おすすめ広告コントローラー.
 */
class RecommendProgramController extends Controller
{
    public function index()
    {
        
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'status' => null],
            function ($params) {
                $now = Carbon::now();
                $builder = RecommendProgram::select('recommend_programs.*');
                if($params['status'] == 1){
                    $builder = $builder->where('recommend_programs.stop_at', '<', $now);
                }elseif($params['status'] == 2){    
                    $builder = $builder->where('recommend_programs.start_at', '<=', $now)
                    ->where('recommend_programs.stop_at', '>=', $now);
                }elseif($params['status'] == 3){
                    $builder = $builder->where('recommend_programs.start_at', '>', $now);
                }else{
                    $builder = $builder;
                }
                $builder = $builder->whereNull('recommend_programs.delete_at');
                $builder = $builder->orderBy('id', 'desc');
                return $builder;
            },
            50
        );
        return view('recommend_program.index', ['recommend_program_list' => $paginator]);
    }
    public function create()
    {
        return $this->edit(RecommendProgram::getDefault());
    }

    public function edit(RecommendProgram $recommend_program)
    {
        return view('recommend_program.edit', ['recommend_program' => $recommend_program]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $recommend_program_list = RecommendProgram::where('program_id',$request->input('program_id'))->where('device_type',$request->input('device_type'))->whereNull('delete_at')->get();
        if ($request->has('id')) {
            $recommend_program = RecommendProgram::findOrFail($request->input('id'));
            $recommend_program_list = $recommend_program_list->reject(function ($item) use ($data) {
                return $item->id == $data['id'];
            });
        } else {
            $recommend_program = RecommendProgram::getDefault();
        }

        $validateRules = [
            'id' => ['nullable', 'integer'],
            'title' => ['required', 'max:255'],
            'device_type' => ['required', Rule::in([7, 1, 6])],
            'program_id' => ['required', 'integer'],
            'sort' => ['required', 'integer'],
            'start_at_date' => ['required', 'date_format:"Y-m-d"'],
            'start_at_time' => ['required', 'date_format:"H:i"'],
            'stop_at_date' => ['required', 'date_format:"Y-m-d"'],
            'stop_at_time' => ['required', 'date_format:"H:i"'],

        ];
        $this->validate(
            $request,
            $validateRules,
            [],
            array_merge(
                [
                    'start_at_date' => '開始日',
                    'start_at_time' => '開始時',
                    'stop_at_date' => '終了日',
                    'stop_at_time' => '終了時',
                    'title'        => '名称',
                ])
        );
        $validatedData['start_at'] = $data['start_at_date'].' '.$data['start_at_time'];
        $validatedData['stop_at'] = $data['stop_at_date'].' '.$data['stop_at_time'];
        $rules = [ 'start_at' => 'before:stop_at'];
        $messages = ['start_at.before' => '開始日時は終了日時以前の日付にしてください'];
        $validator = Validator::make($validatedData, $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        //check time
        $overlapping = false;
        $program_check_time = $recommend_program_list;
        if($program_check_time->count() > 0){
            foreach ($program_check_time as $time_check) {
                $programStart = $time_check['start_at'];
                $programStop = $time_check['stop_at'];
                if ($validatedData['start_at'] < $programStop && $validatedData['stop_at'] > $programStart) {
                    $overlapping = true;
                    break;
                }
            }
            if ($overlapping) {
                $validator = Validator::make([], []);
                $validator->errors()->add('time_period', '時間の重複');
                throw new ValidationException($validator);
            }
        }
        // 開始日時
        $start_at = $data['start_at_date'].' '.$data['start_at_time'];
        $start_at = Carbon::createFromFormat('Y-m-d H:i', $start_at)->format('Y-m-d H:i:s');
        // 終了日時
        $stop_at = $data['stop_at_date'].' '.$data['stop_at_time'].':59';
        $stop_at = Carbon::createFromFormat('Y-m-d H:i:s', $stop_at)->format('Y-m-d H:i:s');
        $item = [
            'title'  => $data['title'],
            'sort' => $data['sort'],
            'device_type' => $data['device_type'],
            'program_id' => $data['program_id'],
            'start_at' => $start_at,
            'stop_at' => $stop_at
        ];
        
        $recommend_program->fill($item);
        // 保存実行
        $res = $recommend_program->saveRecommendProgram();

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '情報の編集に失敗しました');
        }
        return redirect(route('recommend_program.index',['recommend_program_list' => $recommend_program_list]));
    }

    public function destroy(Request $request, $id)
    {
        $recommend_program = RecommendProgram::findOrFail($id);
        $recommend_program->delete_at = Carbon::now();
        $recommend_program->save();
        return redirect(route('recommend_program.index'));
    }

    public function get_program(Request $request){
        $res = $request->all();
        $program = Program::find($res['id']);
        if($program){
            return response()->json(['program_name' => $program->title]);
        }
        return response()->json(['program_name' => '']);
    }
}