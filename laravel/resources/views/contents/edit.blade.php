@extends('layouts.master')

@section('title', 'コンテンツ管理')

@section('menu')
<li>{{ Tag::link(route('contents.list', ['spot' => $spot]), 'コンテンツ一覧') }}</li>
<li{!! (isset($content['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('contents.create', ['spot' => $spot]), '新規コンテンツ登録') }}</li>
@endsection

@section('content')

@php
$attachment_select_params = ['img_ids_id' => 'ContentImgIds'];
if (isset($content['id'])) {
    $attachment_select_params['parent_type'] = 'content';
    $attachment_select_params['parent_id'] = $content['id'];
}
$spot_data = json_decode($spot->data);
@endphp
@include('elements.attachment_select', $attachment_select_params)
        
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('contents.store'), 'method' => 'post', 'files' => true, 'class' => 'LockForm']) }}
@csrf
{{ Tag::formHidden('spot_id', $spot->id) }}
    {{ Tag::formHidden('img_ids', $content['img_ids'], ['id' => 'ContentImgIds']) }}
    <fieldset>
        @if (isset($content['id']))
        <legend>{{ $spot->title }}更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $content['id'] }}
            {{ Tag::formHidden('id', $content['id']) }}
        </div>
        @else
        <legend>{{ $spot->title }}作成</legend>
        @endif
        <div class="form-group">
            <label for="ContentTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $content['title']), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'ContentTitle']) }}<br />
        </div>
        @if ($spot->use_devices == 1)
        <div class="form-group">
            <label for="ContentDevices">対象デバイス</label><br />
            {{ Tag::formSelect('devices', config('map.device2'), old('devices', $content['devices']), ['class' => 'form-control', 'id' => 'ContentDevices']) }}<br />
        </div>
        @else
        {{ Tag::formHidden('devices', old('devices', $content['devices'])) }}
        @endif

        @foreach($spot_data as $key => $info)
        @php
        $input_name = 'data.'.$key;
        $input_key = 'data['.$key.']';
        $input_value = old($input_name, $content['data'][$key] ?? null);
        $input_id = 'ContentData'.strtr(ucwords(strtr($key, ['_' => ' '])), [' ' => '']);
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
        $img_id = 'ContentDataImg';
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
        
        <div class="form-group">
            <label for="ContentPriority">表示順</label><br />
            {{ Tag::formNumber('priority', old('priority', $content['priority']), ['class' => 'form-control', 'required' => 'required', 'id' => 'ContentPriority']) }}<br />
        </div>
        
        <div class="form-group">
            <label for="ContentStartAt">開始日時</label><br />
            {{ Tag::formText('start_at', old('start_at', $content['start_at']), ['class' => 'form-control', 'required' => 'required', 'id' => 'ContentStartAt']) }}<br />
        </div>
        <div class="form-group">
            <label for="ContentStopAt">終了日時</label><br />
            {{ Tag::formText('stop_at', old('stop_at', $content['stop_at']), ['class' => 'form-control', 'id' => 'ContentStopAt']) }}<br />
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
