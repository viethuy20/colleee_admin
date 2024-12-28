<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\Attachment;
use App\Paginators\BasePaginator;
use App\SpProgram;
use App\SpProgramType;

/**
 * 特別プログラム管理コントローラー.
 */
class SpProgramsController extends Controller
{
    /**
     * 特別プログラム検索.
     * @param SpProgramType $sp_program_type 特別プログラム種類
     */
    public function getList(SpProgramType $sp_program_type)
    {
        $paginator = BasePaginator::getDefault(
            ['page' => 1, 'sort' => -1,],
            function ($params) use ($sp_program_type) {
                $builder = SpProgram::where('category_id', '=', $sp_program_type->category_id);

                // ソート
                $sort_key = abs($params['sort']);
                if ($sort_key == 2) {
                    if ($params['sort'] > 0) {
                        $builder = $builder->orderBy('status', 'asc')
                            ->orderBy('stop_at', 'desc');
                    } else {
                        $builder = $builder->orderBy('status', 'desc')
                            ->orderBy('stop_at', 'asc');
                    }
                } else {
                    $sort_v = ($params['sort'] > 0) ? 'asc' : 'desc';
                    $sort_map = [1 => 'id',];
                    $builder = $builder->orderBy($sort_map[$sort_key], $sort_v);
                }
                if ($sort_key != 1) {
                    $builder = $builder->orderBy('id', 'desc');
                }
                return $builder;
            },
            20
        );
        return view('sp_programs.list', ['paginator' => $paginator, 'sp_program_type' => $sp_program_type]);
    }
    
    /**
     * 特別プログラム情報作成.
     * @param SpProgramType $sp_program_type 特別プログラム種類
     */
    public function create(SpProgramType $sp_program_type)
    {
        return $this->edit(SpProgram::getDefault($sp_program_type));
    }
    
    /**
     * 特別プログラム情報更新.
     * @param SpProgram $sp_program 特別プログラム
     */
    public function edit(SpProgram $sp_program)
    {
        // プログラム初期値・入力値を取得
        $sp_program_map = $sp_program->only(['id', 'title', 'img_ids', 'sp_program_type_id',
            'category', 'devices', 'point', 'status', 'test', 'priority', 'memo']);
        $sp_program_map['start_at'] = $sp_program->start_at->format('Y-m-d H:i');
        $sp_program_map['stop_at'] = $sp_program->stop_at->format('Y-m-d H:i');
        $sp_program_map['data'] = isset($sp_program->data) ? json_decode($sp_program->data, true) : null;
        
        return view('sp_programs.edit', ['sp_program_type' => $sp_program->sp_program_type,
            'sp_program' => $sp_program_map]);
    }
    
    /**
     * 特別プログラム情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $sp_program_type_id = $request->input('sp_program_type_id');
        if (!isset($sp_program_type_id)) {
            abort(404, 'Not Found.');
        }
        
        $validateRules = [
            'id' => ['nullable', 'integer',],
            'title' => ['required', 'max:256',],
            'devices' => ['required', 'integer',],
            'point' => ['nullable', 'integer',],
            'test' => ['required', 'integer', 'in:0,1',],
            'priority' => ['required', 'integer',],
            'start_at' => ['required', 'date_format:"Y-m-d H:i"',],
            'stop_at' => ['nullable', 'date_format:"Y-m-d H:i"',],
        ];
        
        $validateAttributes = [
            'id' => 'ID',
            'test' => 'テスト',
            'title' => 'タイトル',
            'img_ids' => '画像ID',
            'devices' => '対象デバイス',
            'point' => 'ポイント',
            'priority' => '優先度',
            'start_at' => '開始日時',
            'stop_at' => '終了日時',
        ];
        
        $sp_program_type = SpProgramType::find($sp_program_type_id);

        $sp_program_type_data = json_decode($sp_program_type->data);
        if (isset($sp_program_type_data)) {
            foreach ($sp_program_type_data as $key => $info) {
                $index = 'data.'.$key;
                $validateAttributes[$index] = $info->label;
                if (isset($info->nullable) && $info->nullable) {
                    $rule_list = ['nullable'];
                } else {
                    $rule_list = ['required'];
                }
                if ($info->type == 'url') {
                    //$rule_list[] = 'url';
                }
                if ($info->type == 'img_url') {
                    $rule_list[] = 'url';
                }
                if ($info->type == 'string') {
                    $rule_list[] = 'max:256';
                }
                if ($info->type == 'number') {
                    $rule_list[] = 'numeric';
                }
                
                $validateRules[$index] = $rule_list;
            }
        }
        
        //
        $this->validate(
            $request,
            $validateRules,
            [],
            $validateAttributes
        );
        
        // 特別プログラム情報
        if ($request->filled('id')) {
            $sp_program = SpProgram::find($request->input('id'));
        } else {
            $sp_program = SpProgram::getDefault($sp_program_type);
            // 画像IDリストを登録
            $sp_program->img_ids = $request->input('img_ids');
        }
                            
        // 特別プログラム
        $sp_program->fill($request->only(['title', 'devices', 'point', 'priority', 'test', 'memo']));
        // 開始日時
        $sp_program->start_at = Carbon::parse($request->input('start_at').':00');
        // 終了日時
        $stop_at = $request->input('stop_at');
        $sp_program->stop_at = isset($stop_at) ? Carbon::parse($request->input('stop_at').':00') :
            $sp_program->start_at->copy()->addYears(40);

        $data = $request->only(['data']);
        $sp_program->data = isset($data['data']) ? json_encode($data['data']) : null;

        // 保存実行
        $res = $sp_program->saveSpProgram();
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '特別プログラム情報の編集に失敗しました');
        }
        
        // 初期登録の際は添付ファイルを有効にする
        if (!$request->filled('id') && isset($sp_program->img_ids)) {
            Attachment::enable(explode(',', $sp_program->img_ids));
        }
        
        return redirect(route('sp_programs.edit', ['sp_program' => $sp_program]))
            ->with('message', '特別プログラム情報の編集に成功しました');
    }
    
    private function changeStatus(SpProgram $sp_program, bool $enable)
    {
        if ($enable) {
            $action = '公開';
            $sp_program->status = 0;
        } else {
            $action = '非公開';
            $sp_program->status = 1;
            $sp_program->deleted_at = Carbon::now();
        }

        // トランザクション処理
        $res = DB::transaction(function () use ($sp_program) {
            // 登録実行
            $sp_program->save();
            return true;
        });

        // 失敗した場合
        $message = empty($res) ? '特別プログラム情報の'.$action.'に失敗しました' : '特別プログラム情報の'.$action.'に成功しました';

        return redirect()
            ->back()
            ->with('message', $message);
    }

    /**
     * 特別プログラム公開.
     * @param SpProgram $sp_program 特別プログラム
     */
    public function enable(SpProgram $sp_program)
    {
        return $this->changeStatus($sp_program, true);
    }
    
    /**
     * 特別プログラム非公開.
     * @param SpProgram $sp_program 特別プログラム
     */
    public function destroy(SpProgram $sp_program)
    {
        return $this->changeStatus($sp_program, false);
    }
}
