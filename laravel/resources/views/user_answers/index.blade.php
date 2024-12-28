@extends('layouts.master')

@section('title', 'アンケートコメント一覧')

@section('head.load')
<script type="text/javascript">
$(function() {
    $('#allChk').on('click', function() {
        $('.edit').prop('checked', this.checked);
    });
});
function collectiveUpdateStatus(status){
    var hasChecked = false;
    $('.edit:checked').each(function(){
        hasChecked = true;
    }).get();
     
    if (!hasChecked){
        alert('コメントが選択されていません。');
        return false;
    }
    
    if (!confirm('\nチェックしたコメントを更新しますか？')){
        return false;
    }
    
    $("#UserAnswerChangeStatusStatus").val(status);
    $("#UserAnswerChangeStatusForm").submit();
    return true;
}
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('questions.index'), 'デイリーアンケート一覧') }}</li>
<li>{{ Tag::link(route('questions.create'), '新規デイリーアンケート登録') }}</li>
<li class="active">{{ Tag::link(route('user_answers.index'), 'アンケートコメント一覧') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('user_answers.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="QuestionId">アンケートID</label>
        {{ Tag::formText('question_id', $paginator->getQuery('question_id') ?? '', ['class' => 'form-control', 'id' => 'QuestionId']) }}
    </div>
    <div class="form-group">
        <label for="UserName">ユーザーID</label>
        {{ Tag::formText('user_name', $paginator->getQuery('user_name') ?? '', ['class' => 'form-control', 'id' => 'UserName']) }}
    </div>
    <div class="form-group">
        <label for="Ip">IPアドレス</label>
        {{ Tag::formText('ip', $paginator->getQuery('ip') ?? '', ['class' => 'form-control', 'id' => 'Ip']) }}
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>コメント情報</h2>

{{ Tag::formOpen(['method' => 'POST', 'url' => route('user_answers.change_status'), 'id' => 'UserAnswerListForm']) }}
@csrf    
{{ Tag::formHidden('status', '', ['id' => 'UserAnswerChangeStatusStatus']) }}

    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
        <tr>
            <th rowspan="2">{{ Tag::formCheckbox('allChk', 'allChk', null, ['id' => 'allChk']) }}全てにチェック</th>
            <th rowspan="2">状態</th>
            <th rowspan="2">アンケートID</th>
            <th>ユーザーID</th>
            <th>コメント</th>
            <th rowspan="2">回答日時</th>
        </tr>
        <tr>
            <th>IPアドレス</th>
            <th>ユーザーエージェント</th>
        </tr>
        @php
        $status_map = [0 => ['class' => 'active', 'status' => '表示'],
            1 => ['class' => 'warning', 'status' => '非表示']];
        @endphp
        @forelse ($paginator as $index => $user_answer)
        @php
        $state_id = $user_answer->status == 0 ? 0 : 1;
        @endphp
        <tr class="{{ $status_map[$state_id]['class'] }}">
            <td rowspan="2">{{ Tag::formCheckbox('id[]', $user_answer->id, null, ['class' => 'edit']) }}</td>
            <td rowspan="2">{{ $status_map[$state_id]['status'] }}&nbsp;</td>
            <td rowspan="2">{{ $user_answer->question_id }}&nbsp;</td>
            <td>{{ Tag::link(route('users.edit', ['user' => $user_answer->user_id]), \App\User::getNameById($user_answer->user_id)) }}&nbsp;</td>
            <td>{{ $user_answer->message }}&nbsp;</td>
            <td rowspan="2">{{ $user_answer->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        </tr>
        <tr class="{{ $status_map[$state_id]['class'] }}">
            <td>{{ $user_answer->ip }}&nbsp;</td>
            <td>{{ $user_answer->ua }}&nbsp;</td>
        </tr>
        @empty
        <tr><td colspan="6">コメントは存在しません</td></tr>
        @endforelse
    </table>

    チェック入れたものを操作<br />
    {{ Tag::formSubmit('表示', ['id' => 'approval_btn', 'class' => 'btn btn-success btn-small', 'style' => 'float:left;', 'onclick' => "return collectiveUpdateStatus(0);"]) }}
    {{ Tag::formSubmit('非表示', ['id' => 'reject_btn', 'class' => 'btn btn-danger btn-small', 'onclick' => "return collectiveUpdateStatus(1);"]) }}
{{ Tag::formClose() }}

{!! $paginator->links() !!}

@endsection
