@extends('layouts.master')

@section('title', 'デイリーアンケート管理')

@section('menu')
<li class="active">{{ Tag::link(route('questions.index'), 'デイリーアンケート一覧') }}</li>
<li>{{ Tag::link(route('questions.create'), '新規デイリーアンケート登録') }}</li>
<li>{{ Tag::link(route('user_answers.index'), 'アンケートコメント一覧') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('questions.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="QuestionId">アンケートID</label>
        {{ Tag::formText('question_id', $paginator->getQuery('question_id') ?? '', ['class' => 'form-control', 'id' => 'QuestionId']) }}
    </div>
    <div class="form-group">
        <label for="QuestionTitle">タイトル</label>
        {{ Tag::formText('title', $paginator->getQuery('title') ?? '', ['class' => 'form-control', 'id' => 'QuestionTitle']) }}
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

<h2>アンケート</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions" rowspan="2">操作</th>
        <th rowspan="2">ID</th>
        <th rowspan="2">タイトル</th>
        <th rowspan="2">状態</th>
        <th>開始日時</th>
        <th>作成日時</th>
    </tr>
    <tr><th>終了日時</th><th>更新日時</th></tr>
    @php
    $now = Carbon\Carbon::now();
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        1 => ['class' => 'warning', 'status' => '下書き'],
        2 => ['class' => 'warning', 'status' => '公開待ち'],
        3 => ['class' => 'danger', 'status' => '公開終了'],
        4 => ['class' => 'danger', 'status' => '削除']];
    @endphp
    @forelse ($paginator as $index => $question)
    @php
    $state_id = 0;
    if ($question->status == 1) {
        $state_id = 4;
    } elseif ($question->status == 2) {
        $state_id = 1;
    } elseif($question->start_at->gt($now)) {
        $state_id = 2;
    } elseif($question->stop_at->lt($now)) {
        $state_id = 3;
    }
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions" style="white-space:nowrap" rowspan="2">
            @if ($question->status != 1)
            {{ Tag::link(route('questions.edit', ['question' => $question]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            @endif
            @if ($question->status == 2)
            {{ Tag::formOpen(['url' => route('questions.enable')]) }}
            @csrf
            {{ Tag::formHidden('id', $question->id) }}
                {{ Tag::formText('start_at', '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'YYYY-MM-DD']) }}
                @if ($question->type != 1)
                ～{{ Tag::formText('stop_at', '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'YYYY-MM-DD']) }}<br />
                @endif
                {{ Tag::formSubmit('公開', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('このアンケートを公開しますか?:".$question->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($question->status != 1)
            {{ Tag::formOpen(['url' => route('questions.destroy', ['question' => $question])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('非公開', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('このアンケートを非公開にしますか?:".$question->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td rowspan="2">{{ $question->id }}&nbsp;</td>
        <td rowspan="2">{{ $question->title }}&nbsp;</td>
        <td rowspan="2">{{ $status_map[$state_id]['status'] }}&nbsp;</td>
        <td>{{ isset($question->start_at) ? $question->start_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
        <td>{{ $question->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td>{{ isset($question->stop_at) ? $question->stop_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
        <td>{{ $question->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="6">アンケートは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
