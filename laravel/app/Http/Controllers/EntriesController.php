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
use App\Entries;
use App\Paginators\BasePaginator;
/**
 * プログラム管理コントローラー.
 */
class EntriesController extends Controller
{
    public function index()
    {
        //$entries_list = Entries::all();
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'status' => null],
            function ($params) {
                $now = Carbon::now();
                $builder = Entries::select('entries.*');
                if($params['status'] == 1){
                    $builder = $builder->where('entries.stop_at', '<', $now);
                }elseif($params['status'] == 2){    
                    $builder = $builder->where('entries.start_at', '<=', $now)
                    ->where('entries.stop_at', '>=', $now);
                }elseif($params['status'] == 3){
                    $builder = $builder->where('entries.start_at', '>', $now);
                }else{
                    $builder = $builder;
                }
                $builder = $builder->orderBy('id', 'desc');
                return $builder;
            },
            50
        );
        return view('entries.index', ['entries_list' => $paginator]);
    }
    public function create()
    {
        return $this->edit(Entries::getDefault());
    }

    public function edit(Entries $entry)
    {
        return view('entries.edit', ['entry' => $entry]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $entries_list = Entries::all();
        if ($request->filled('id')) {
            $entries = Entries::findOrFail($request->input('id'));
            $entries_list = $entries_list->reject(function ($item) use ($data) {
                return $item->id == $data['id'];
            });
        } else {
            $entries = Entries::getDefault();
        }

        $validateRules = [
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
                    'title'        => '告知名称',
                    'main_text_pc' => 'PCテキスト（メイン）',
                    'sub_text_pc' => 'PCテキスト（サブ）',
                    'main_text_sp' => 'SPテキスト（メイン）',
                    'sub_text_sp' => 'SPテキスト（サブ）',
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
        $entries_check_time = $entries_list;
        if($entries_check_time->count() > 0){
            foreach ($entries_check_time as $time_check) {
                $entriesStart = $time_check['start_at'];
                $entriesStop = $time_check['stop_at'];
                if ($validatedData['start_at'] < $entriesStop && $validatedData['stop_at'] > $entriesStart) {
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
        //case 改行は保存しない
        $sub_text_pc = str_replace(["\r", "\n"], '', $data['sub_text_pc']);
        $entries_item = [
            'title'  => $data['title'],
            'sub_text_pc' => $sub_text_pc,
            'main_text_pc' => $data['main_text_pc'],
            'sub_text_sp' => $data['sub_text_sp'],
            'main_text_sp' => $data['main_text_sp'],
            'start_at' => $start_at,
            'stop_at' => $stop_at
        ];
        
        $entries->fill($entries_item);
        // 保存実行
        $res = $entries->saveEntries(Auth::user()->id);

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '情報の編集に失敗しました');
        }
        return redirect(route('entries.index',['entries_list' => $entries_list]));
    }
}