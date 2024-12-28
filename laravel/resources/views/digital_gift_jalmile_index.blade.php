@extends('layouts.master')

@section('title', 'デジタルギフトJALマイル管理')

@section('menu')
@include('partials.menu.point_exchange_menu', ['currentRoute' => 'jalmile.index'])
@endsection

@php
$exchange_status_map = [0 => '発行済み', 1 => '組戻し', 2 => '申し込み中', 4 => 'コード削除'];
@endphp
@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('jalmile.index'), 'method' => 'get']) }}
@csrf
    <div class="form-group">
        <label for="ExchangeRequestNumber">番号</label>
        {{ Tag::formText('number', $paginator->getQuery('number') ?? '', ['class' => 'form-control', 'id' => 'ExchangeRequestNumber']) }}
    </div>
    <div class="form-group">
        <label for="ExchangeRequestUserName">ユーザーID</label>
        {{ Tag::formText('user_name', $paginator->getQuery('user_name') ?? '', ['class' => 'form-control', 'id' => 'ExchangeRequestUserName']) }}
    </div>
    <div class="form-group">
        <label for="ExchangeRequestStatus">状態</label>
        {{ Tag::formSelect('status', ['' => '---'] + $exchange_status_map, $paginator->getQuery('status') ?? '', ['class' => 'form-control', 'id' => 'ExchangeRequestStatus']) }}
    </div>
    <div class="form-group">
        <label for="ExchangeRequestEndAt">終了日時</label>
        {{ Tag::formText('end_at', $paginator->getQuery('end_at') ?? '', ['class' => 'form-control', 'id' => 'ExchangeRequestEndAt']) }}
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>デジタルギフトJALマイル申し込み一覧</h2>

<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions" rowspan="2">操作</th>
        <th rowspan="2">申し込み番号</th>
        <th rowspan="2">ユーザー</th>
        <th>消費ポイント数</th>
        <th rowspan="2">結果</th>
        <th rowspan="2">額面</th>
        <th colspan="2">申し込み日時</th>
    </tr>
    <tr>
        <th>状態</th>
        <th>受付</th>
        <th>実行</th>
    </tr>
    @forelse ($paginator as $index => $exchange_request)
    @php
    $digital_gift_code = $exchange_request->digital_gift_code ? $exchange_request->digital_gift_code: '';
    $digital_gift_expire_at = $exchange_request->digital_gift_expire_at ? $exchange_request->digital_gift_expire_at: '';
    @endphp
    <tr>
        <td rowspan="2" class="actions" style="white-space:nowrap">
            @if ($exchange_request->status == \App\ExchangeRequest::SUCCESS_STATUS)
            {{ Tag::formOpen(['url' => route('jalmile.resend', ['exchange_request' => $exchange_request->id])]) }}
            @csrf    
            {{ Tag::formSubmit('再送信', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('ポイント交換URLを再送信しますか?:".$exchange_request->number."');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td rowspan="2">{{ $exchange_request->number }}&nbsp;</td>
        <td rowspan="2">{{ $exchange_request->user_name }}&nbsp;</td>
        <td>{{ number_format($exchange_request->point) }}ポイント&nbsp;</td>
        <td rowspan="2">
            @if (!empty($digital_gift_code))
            コード:<br />{{ $digital_gift_code }}<br />
            @if (!empty($digital_gift_expire_at))
            有効期限:<br />{{ $digital_gift_expire_at }}<br />
            @endif
            @else
            @if (!empty($exchange_request->response_code))
            ステータス:<br />
            {{ $exchange_request->response_code }}<br />
            @endif
            @endif
            &nbsp;
        </td>
        <td rowspan="2">{{ $exchange_request->face_value_label }}&nbsp;</td>
        <td rowspan="2">{{ $exchange_request->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td rowspan="2">{{ isset($exchange_request->requested_at) ? $exchange_request->requested_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    <tr>
        <td>{{ $exchange_status_map[$exchange_request->status] }}&nbsp;</td>
        
    </tr>
    @empty
    <tr><td colspan="8">デジタルギフト申し込みは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
