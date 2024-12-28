@extends('layouts.master')

@section('title', 'PayPayポイント管理')

@section('menu')
@include('partials.menu.point_exchange_menu', ['currentRoute' => 'paypay.index'])
@endsection

@php
$exchange_status_map = config('paypay.status');
@endphp
@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('paypay.index'), 'method' => 'get']) }}
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

<h2>PayPayポイント申し込み一覧</h2>
@if (isset($message) && $message!='')
<div class="alert alert-warning">
    {{ $message }}
</div>
@endif
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions" rowspan="2">操作</th>
        <th rowspan="2">申し込み番号</th>
        <th rowspan="2">ユーザー</th>
        <th>消費ポイント数</th>
        <th rowspan="2">状態</th>
        <th rowspan="2">結果</th>
        <th colspan="3">申し込み日時</th>
    </tr>
    <tr>
        <th>額面</th>
        <th>受付</th>
        <th>実行</th>
        <th>更新</th>
    </tr>
    @forelse ($paginator as $index => $exchange_request)
    <tr>
        <td rowspan="2" class="actions" style="white-space:nowrap">
            @if ($exchange_request->status == \App\ExchangeRequest::PAYPAY_RETRY_STATUS)
            {{ Tag::formOpen(['url' => route('paypay.rollback', ['exchange_request_id' => $exchange_request->id])]) }}
            @csrf    
            {{ Tag::formSubmit('組戻し', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('組み戻しを実行しますか?');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td rowspan="2">{{ $exchange_request->number }}&nbsp;</td>
        <td rowspan="2">{{ $exchange_request->user_name }}&nbsp;</td>
        <td>{{ number_format($exchange_request->point) }}ポイント&nbsp;</td>
        <td rowspan="2">{{ $exchange_status_map[$exchange_request->status] }}&nbsp;</td>
        <td rowspan="2">
            @if (isset($exchange_request->exchange_request_cashback_key->cashback_id))
            CashBack ID: {{ $exchange_request->exchange_request_cashback_key->cashback_id }}<br />
            @endif
            @if (isset($exchange_request->response_code))
            コード: {{ $exchange_request->response_code }}<br />
            @endif
            &nbsp;
        </td>
        <td rowspan="2">{{ $exchange_request->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td rowspan="2">{{ isset($exchange_request->requested_at) ? $exchange_request->requested_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
        <td rowspan="2">{{ $exchange_request->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    <tr><td>{{ $exchange_request->face_value_label }}&nbsp;</td></tr>
    @empty
    <tr><td colspan="7">PayPaysポイント込申し込みは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
