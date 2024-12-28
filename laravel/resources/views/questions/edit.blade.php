@extends('layouts.master')

@section('title', 'デイリーアンケート管理')

@section('menu')
<li>{{ Tag::link(route('questions.index'), 'デイリーアンケート一覧') }}</li>
<li{!! (isset($question['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('questions.create'), '新規デイリーアンケート登録') }}</li>
<li>{{ Tag::link(route('user_answers.index'), 'アンケートコメント一覧') }}</li>
@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('questions.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($question['id']))
        <legend>デイリーアンケート更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $question['id'] }}
            {{ Tag::formHidden('id', $question['id']) }}
        </div>
        @else
        <legend>デイリーアンケート作成</legend>
        @endif
        
        <div class="form-group">
            <label for="QuestionTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $question['title']), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'QuestionTitle']) }}<br />
        </div>

        @for ($i = 0; $i < 10; $i++)
        @php
        $answer_id = $i + 1;
        $text_id = sprintf("QuestionAnswer%d", $answer_id);
        $text_attr = ['class' => 'form-control', 'maxlength' => '256', 'id' => $text_id];
        if ($i < 2) {
            $text_attr['required'] = 'required';
        }
        $base_name = sprintf("answer.%d", $i);
        $base_key = sprintf("answer[%d]", $i);
        @endphp
        <div class="form-group">
            <label for="{{ $text_id }}">回答{{ $answer_id }}</label><br />
            {{ Tag::formHidden($base_key.'[id]', $answer_id) }}
            {{ Tag::formText($base_key.'[label]', old($base_name.'.label', $question['answer'][$i]->label ?? ''), $text_attr) }}<br />
        </div>
        @endfor
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
