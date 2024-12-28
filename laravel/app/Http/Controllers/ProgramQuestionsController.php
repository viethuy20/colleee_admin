<?php

namespace App\Http\Controllers;
use App\ProgramQuestion;
use App\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProgramQuestionsController extends Controller
{
    //
    public function create(int $maxDispOrder = null, Program $program)
    {
        return $this->edit($program->default_question, $maxDispOrder);
    }

    public function edit(ProgramQuestion $program_question, int $maxDispOrder = null)
    {
        $program = $program_question->program;
        $datas = ['program' => $program, 'question' => $program_question, 'maxDispOrder' => $maxDispOrder];
        return view('programs.questions', $datas);
    }

    public function store(Request $request)
    {
        // 初期データを取得
        if ($request->filled('id')) {
            $question = ProgramQuestion::findOrFail($request->input('id'));
        } else {
            if (!$request->filled('program_id')) {
                abort(404, 'Not Found.');
            }
            $program = Program::findOrFail($request->input('program_id'));
            $question = $program->default_question;
        }

        $program_id = $request->input('program_id');

        $pointValidatateMsg = [];
        $validateRules = [
            'program_id' => ['nullable', 'integer',],
            'question' => ['required'],
            'answer' => ['required'],
            'disp_order' => ['integer', 'between:1,999'],

        ];
        $this->validate(
            $request,
            $validateRules,
            [],
            array_merge(
                [
                    'program_id' => 'プログラムID',
                    'question' => '質問名',
                    'answer' => '回答',
                    'disp_order' => '表示順',
                ],
                $pointValidatateMsg
            )
        );


        $question->fill($request->only(['program_id', 'question', 'answer', 'disp_order']));
        // 保存実行
        $res = $question->saveQuestion(Auth::user()->id);

        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'よくある質問情報の編集に失敗しました');
        }

        return redirect(route('programs.edit', ['program' => $question->program]))
            ->with('message', 'よくある質問情報の編集に成功しました');
    }

    public function destroy(ProgramQuestion $program_question)
    {
        $program_question->delete();
        return redirect(route('programs.edit', ['program' => $program_question->program]));
    }
}
