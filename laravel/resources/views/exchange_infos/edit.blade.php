@php
$exchange = config('exchange.point.'.$exchange_info['type']);
@endphp
@extends('layouts.master')

@section('title', $exchange['label'])

@section('menu')

<li class="active">{{ Tag::link(route('exchange_infos.index'), '交換先一覧') }}</li>
@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>{{ $exchange['label'] }}</h2>
@php
$show_map = [0 => '公開', 1 => '停止'];
@endphp
{{ Tag::formOpen(['url' => route('exchange_infos.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($exchange_info['id']))
        <legend>編集</legend>
        {{ Tag::formHidden('id', old('id', $exchange_info['id'] ?? '')) }}
        @else
        <legend>作成</legend>
        {{ Tag::formHidden('type', old('type', $exchange_info['type'] ?? '')) }}
        @endif

        <div class="form-group">
            <label for="ExchangeInfoStatus">状態</label><br />
            @if ($exchange_info['started'])
            {{ $show_map[$exchange_info['status']] }}
            @else
            {{ Tag::formSelect('status', ['' => '---'] + $show_map, old('status', $exchange_info['status'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'ExchangeInfoStatus']) }}
            @endif
        </div>
        <div class="form-group">
            <label for="ExchangeInfoYenRate">円交換比率(%)</label><br />
            @if ($exchange_info['started'])
            {{ $exchange_info['yen_rate'] }}
            @else
            {{ Tag::formNumber('yen_rate', old('yen_rate', $exchange_info['yen_rate']), ['class' => 'form-control', 'required' => 'required', 'id' => 'ExchangeInfoYenRate']) }}
            @endif
        </div>

        <div class="form-group">
            <label for="ExchangeInfoStartAt">実施期間</label>
            <div class="form-inline">
                @if ($exchange_info['started'])
                {{ $exchange_info['start_at'] }}
                @else
                {{ Tag::formText('start_at', old('start_at', $exchange_info['start_at']), ['class' => 'form-control', 'id' => 'ExchangeInfoStartAt']) }}
                @endif
                ～
                @if ($exchange_info['stop_at'] != '9999-12-31 23:59')
                {{ $exchange_info['stop_at'] }}
                @endif
            </div>
        </div>

        <hr />

        <legend>メッセージ</legend>
        <div class="form-group"><table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
            <tr>
                <th>開始日</th>
                <th>内容</th>
            </tr>
            @foreach ($message_list as $message)
            <tr>
                <td>{{ $message->start_at->format('Y-m-d H:i') }}</td>
                <td>{!! nl2br(e($message->body)) !!}</td>
            </tr>
            @endforeach
            @if (!$exchange_info['stopped'])
            @foreach ($next_message_list as $key => $next_message)
            <tr>
                <td>{{ Tag::formText('message['.$key.'][start_at]', old('message.'.$key.'.start_at', $next_message['start_at'] ?? null), ['class' => 'form-control']) }}</td>
                <td>{{ Tag::formTextarea('message['.$key.'][body]', old('message.'.$key.'.body', $next_message['body'] ?? null), ['class' => 'form-control', 'rows' => 3]) }}</td>
            </tr>
            @endforeach
            @endif
        </table></div>

        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
