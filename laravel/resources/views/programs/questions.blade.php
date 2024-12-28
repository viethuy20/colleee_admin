@extends('layouts.master')

@section('title', 'プログラム管理')

@section('head.load')

<script type="text/javascript">
<!--
    $(function(){
        tinyMCE.init({
            mode: "textareas",
            selector:"#Answer1",
            language: "ja",
            toolbar: "forecolor link bullist image code",
            plugins: "textcolor link lists image imagetools code autoresize",
            fullpage_default_doctype: "",
            fullpage_default_encoding: "UTF-8",
            menubar: false,
            statusbar: false,
            cleanup : false,
            force_br_newlines : true,
            force_p_newlines : false,
            forced_root_block : '',
            document_base_url : "{{ config('app.client_url') }}",
            convert_urls : false,
            file_picker_callback : function(callback, value, meta) {
                imageFilePicker(callback, value, meta);
            },
            imagetools_toolbar: "rotateleft rotateright | flipv fliph | editimage imageoptions"
        });

        @if (!isset($program['id']))
        const answerId = 'Answer';
        $(`[id^=${answerId}]`).each(function(element) {
            const index = $(this).attr('id').match(/\d+$/)[0];
            bindSchedule(answerId, index);
        });
        @endif
        //remove question
        @if (isset($question->id))
        $('#destoryBtn').on('click', function() {
        const result = confirm({!! '"【' . $question->question . '】を削除しますか？"' !!} );
        if (result) {
            $('#destoryForm').submit();
        }
        });
        @endif

    });

    function bindSchedule(answerId, No) {
        tinyMCE.init({
            mode: "textareas",
            selector: `#${answerId}${No}`,
            language: "ja",
            toolbar: "forecolor link bullist code",
            plugins: "textcolor link lists code",
            fullpage_default_doctype: "",
            fullpage_default_encoding: "UTF-8",
            menubar: false,
            statusbar: false,
            cleanup : false,
            force_br_newlines : true,
            force_p_newlines : false,
            forced_root_block : '',
            document_base_url : "{{ config('app.client_url') }}",
            convert_urls : false
        });
    }

//get num
function getNum(element)
{
    var parentElement = $(element).parent().parent().parent();
    var questionId = parentElement.length > 0 ? parentElement.find('.question-group').last() : null;
    var getid = questionId && questionId.attr('id') ? questionId.attr('id').match(/\d$/) : null;
    var digit = getid ? getid[0] : null;
    if (digit){
        var id = parseInt(digit) + 1;
        return id ;
    }
    return 1;
}
//add
function addQuestion(element)
{
    const maxDispOrder = {{ isset($maxDispOrder) ? $maxDispOrder : 1 }};
    const data = { id: getNum(element) };
    addQuestionLayout(data, maxDispOrder);
}
function addQuestionLayout (data, max)
{
    const questionUrl = '{{ route('programs.add_question') }}';
    $.ajax({
        type: 'GET',
        url: questionUrl,
        data: {data:data, max: max},
        dataType: 'html',
        success: function(res) {
            $('#questionDetail').append(res);
            bindSchedule('Answer', data.id);
        },

    });
}

// remove
function removeQuestion(event) {
    const id = event.parentNode.parentNode.id.match(/\d+$/);
    $(`#question${id}`).remove();
}

// -->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
@if (isset($program['id']))
<li>{{ Tag::link(route('programs.edit', ['program' => $program['id']]), 'プログラム更新') }}</li>
@endif
@endsection

@section('menu.extra')
@endsection
@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif
<!-- プログラム複写 -->
{{ Tag::formOpen(['url' => route('program_questions.store'), 'method' => 'post', 'files' => true, 'id' => 'LockFormId', 'class' => 'LockForm']) }}
@csrf
<fieldset>
    <legend>プログラム情報</legend>
    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
        <tr>
            <th style="width: 30%;">ID</th>
            <th style="width: 70%;">タイトル</th>
        </tr>
        <tbody>
            <tr>
                <td>
                    {{ $program->id}}
                </td>
                <td>
                    {{ $program->title }}
                </td>
            </tr>
        </tbody>
    </table>
        <div>
            <legend>
            @if (isset($question->id))
            よくある質問更新
            {{ Tag::formHidden('id', $question->id) }}
            {{ Tag::formButton('削除', ['id' => 'destoryBtn', 'class' => 'btn btn-small btn-danger', 'style' => 'margin: 0 20px; border-radius: 4px;',]) }}
            @else
            よくある質問作成
            {{ Tag::formHidden('program_id', $program->id) }}
            @endif
            </legend>
        </div>
        <div id="questionDetail">
                <div class="question-group" id="question1">
                    <div class="form-group">
                        <label for="programQuestion1">質問</label>
                        {{ Tag::formText('question', old('question', $question['question'] ?? ''), ['class' => 'form-control', 'required' => 'required']) }}
                    </div>
                    <div class="form-group">
                        <label for="programAnwer1">回答</label>
                        {{ Tag::formTextarea('answer', old('answer', $question['answer'] ?? ''), ['class' => 'form-control', 'id' => 'Answer1']) }}
                    </div>
                    <div class="form-group">
                        <label for="programOder1">表示順</label>
                        {{ Tag::formNumber('disp_order', old('disp_order', $question['disp_order'] ?? (isset($maxDispOrder) ? $maxDispOrder : 1)), ['class' => 'form-control', 'required' => 'required']) }}
                    </div>
                </div>
        </div>
        <div class="form-group">{{ Tag::formSubmit('保存する', ['class' => 'btn btn-default btn-lg save-all']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

    @if (isset($question->id))
    {{ Tag::formOpen(['url' => route('program_questions.destroy', ['program_question' => $question]), 'id' => 'destoryForm']) }}
    @csrf
    @method('DELETE')
    {{ Tag::formClose() }}
    @endif

@endsection
