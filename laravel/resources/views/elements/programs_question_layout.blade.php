
@if(isset($question_no))
    <div class="question-group" id="question{{ $question_no }}">
        <div style="font-weight: 600;">
            よくある質問{{ $question_no }}
            <a class="btn btn-danger btn-small removenode"  hrerf="javascript:void(0);" onclick="removeQuestion(this)">削除</a><br>
        </div>

        <div class="form-group">
            <label for="programQuestion{{ $question_no }}">質問</label>
            {{ Tag::formText('questions['. $question_no .'][question]', old('questions.question', ''), ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group">
            <label for="programAnwer{{ $question_no }}">回答</label>
            {{ Tag::formTextarea('questions['. $question_no .'][answer]', old('questions.answer', ''), ['class' => 'form-control', 'id' => 'Answer'. $question_no .'']) }}
        </div>
        <div class="form-group">
            <label for="programOder{{ $question_no }}">表示順</label>
            {{ Tag::formNumber('questions['. $question_no .'][disp_order]', old('questions.disp_order', (isset($max) ? ($max + $question_no - 1)  : $question_no)), ['class' => 'form-control', 'required' => 'required']) }}
        </div>
    </div>

@endif

@if(isset($questions))
    @foreach ($questions as $question_no => $question)
    <div class="question-group" id="question{{ $question_no }}">
        <div style="font-weight: 600;">
            よくある質問{{ $question_no }}
            <a class="btn btn-danger btn-small removenode"  hrerf="javascript:void(0);" onclick="removeQuestion(this)">削除</a><br>
        </div>

        <div class="form-group">
            <label for="programQuestion{{ $question_no }}">質問</label>
            {{ Tag::formText('questions['. $question_no .'][question]', old('questions.question', $question['question'] ?? ''), ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group">
            <label for="programAnwer{{ $question_no }}">回答</label>
            {{ Tag::formTextarea('questions['. $question_no .'][answer]', old('questions.answer', $question['answer'] ?? ''), ['class' => 'form-control', 'id' => 'Answer'. $question_no .'']) }}
        </div>
        <div class="form-group">
            <label for="programOder{{ $question_no }}">表示順</label>
            {{ Tag::formNumber('questions['. $question_no .'][disp_order]', old('questions.disp_order', $question['disp_order'] ?? (isset($max) ? ($max + $question_no - 1)  : $question_no)), ['class' => 'form-control', 'required' => 'required']) }}
        </div>
    </div>
    @endforeach
@endif

