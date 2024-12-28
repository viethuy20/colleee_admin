@extends('layouts.master')

@section('title', 'メンテナンス管理')

@section('menu')
<li>{{ Tag::link(route('maintes.index'), 'メンテナンス管理') }}</li>
@endsection

@section('content')
@php
$mainte_name = config('mainte.type')[$mainte['type']];
@endphp
<h2>{{ $mainte_name }}メンテナンス</h2>

{{ Tag::formOpen(['url' => route('maintes.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        {{ Tag::formHidden('type', old('type', $mainte['type'] ?? '')) }}
        @if (isset($mainte['id']))
        {{ Tag::formHidden('id', old('id', $mainte['id'] ?? '')) }}        
        @endif
        <div class="form-group">
            <label for="MainteStartAt">開始日時</label><br />
            @php
            $start_at = old('start_at', $mainte['start_at'] ?? null);
            @endphp
            {{ Tag::formText('start_at', $start_at, ['class' => 'form-control', 'id' => 'MainteStartAt']) }}
        </div>

        <div class="form-group">
            <label for="MainteMessage">内容</label>
            {{ Tag::formTextarea('message', old('message', $mainte['message'] ?? null), ['class' => 'form-control', 'rows' => 3, 'id' => 'MainteMessage']) }}<br />
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
