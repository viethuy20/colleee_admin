@extends('layouts.master')

@section('title', $sp_program_type->title.'管理')

@section('menu')
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
<li>{{ Tag::link(route('sp_programs.create', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'新規登録') }}</li>
@endsection

@section('content')
@php
$attachment_select_params = ['img_ids_id' => 'SpProgramImgIds'];
if (isset($sp_program['id'])) {
    $attachment_select_params['parent_type'] = 'sp_program';
    $attachment_select_params['parent_id'] = $sp_program['id'];
}
$sp_program_type_data = isset($sp_program_type->data) ? json_decode($sp_program_type->data) : null;
@endphp
@include('elements.attachment_select', $attachment_select_params)
        
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('sp_programs.store'), 'method' => 'post', 'files' => true, 'class' => 'LockForm']) }}
@csrf    
{{ Tag::formHidden('sp_program_type_id', old('sp_program_type_id', $sp_program['sp_program_type_id'])) }}
    {{ Tag::formHidden('img_ids', old('img_ids', $sp_program['img_ids']), ['id' => 'SpProgramImgIds']) }}
    <fieldset>
        @if (isset($sp_program['id']))
        <legend>{{ $sp_program_type->title }}更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $sp_program['id'] }}
            {{ Tag::formHidden('id', old('id', $sp_program['id'])) }}
        </div>
        @else
        <legend>{{ $sp_program_type->title }}作成</legend>
        @endif
        <div class="form-group">
            <label for="SpProgramTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $sp_program['title']), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'SpProgramTitle']) }}<br />
        </div>
        @if ($sp_program_type->use_devices == 1)
        <div class="form-group">
            <label for="SpProgramDevices">対象デバイス</label><br />
            {{ Tag::formSelect('devices', config('map.device2'), old('devices', $sp_program['devices']), ['class' => 'form-control', 'id' => 'SpProgramDevices']) }}<br />
        </div>
        @else
        {{ Tag::formHidden('devices', old('devices', $sp_program['devices'])) }}
        @endif

        <div class="form-group">
            <label for="SpProgramPoint">ポイント</label><br />
            {{ Tag::formText('point', old('point', $sp_program['point']), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'SpProgramPoint']) }}<br />
        </div>
        
        @if (isset($sp_program_type_data))
        @foreach($sp_program_type_data as $key => $info)
        @php
        $input_name = 'data.'.$key;
        $input_key = 'data['.$key.']';
        $input_value = old($input_name, $sp_program['data'][$key] ?? null);
        $input_id = 'SpProgramData'.strtr(ucwords(strtr($key, ['_' => ' '])), [' ' => '']);
        $attributes = ['class' => 'form-control', 'id' => $input_id];
        if (!isset($info->nullable) || !$info->nullable) {
            $attributes['required'] = 'required';
        }
        @endphp
        <label for="{{ $input_id }}">{{ $info->label }}</label><br />
        @if ($info->type == 'url')
        @php 
        $attributes['maxlength'] = 256;
        @endphp
        {{ Tag::formText($input_key, $input_value, $attributes) }}<br />
        @elseif ($info->type == 'img_url')
        @php
        $img_id = 'SpProgramDataImg';
        $attributes['class'] = $attributes['class'].' fileUrl';
        $attributes['maxlength'] = 256;
        $attributes['forImg'] = $img_id;
        @endphp
        @if (isset($input_value))
        {{ Tag::image($input_value, 'img', ['id' => $img_id, 'width' => '120px']) }}
        @else
        <img id="{{ $img_id }}" alt="img" width="120px" style="display:none" />
        @endif
        {{ Tag::formText($input_key, $input_value, $attributes) }}<br />
        <input type="button" onclick="openImageDialog('{{ $img_id }}', '{{ $input_id }}');" value="参照" /><br />
        @elseif ($info->type == 'string')
        @php
        $attributes['maxlength'] = 256;
        @endphp
        {{ Tag::formText($input_key, $input_value, $attributes) }}<br />
        @elseif ($info->type == 'number')
        {{ Tag::formNumber($input_key, $input_value, $attributes) }}<br />
        @endif
        @endforeach
        @endif
        
        <div class="form-group"><div class="form-inline">
            <div class="form-group">
                <label for="SpProgramTest">テスト</label><br />
                {{ Tag::formSelect('test', config('map.test'), old('test', $sp_program['test'] ?? ''), ['class' => 'form-control', 'id' => 'SpProgramTest']) }}<br />
            </div>
            <div class="form-group">
                <label for="SpProgramPriority">表示順</label><br />
                {{ Tag::formNumber('priority', old('priority', $sp_program['priority']), ['class' => 'form-control', 'required' => 'required', 'id' => 'SpProgramPriority']) }}<br />
            </div>
        </div></div>
        
        <div class="form-group">
            <label for="SpProgramStartAt">開始日時</label><br />
            {{ Tag::formText('start_at', old('start_at', $sp_program['start_at']), ['class' => 'form-control', 'required' => 'required', 'id' => 'SpProgramStartAt']) }}<br />
        </div>
        <div class="form-group">
            <label for="SpProgramStopAt">終了日時</label><br />
            {{ Tag::formText('stop_at', old('stop_at', $sp_program['stop_at']), ['class' => 'form-control', 'id' => 'SpProgramStopAt']) }}<br />
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
