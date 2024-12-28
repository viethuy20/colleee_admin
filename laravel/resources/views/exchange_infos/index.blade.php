@extends('layouts.master')

@section('title', '交換先一覧')

@section('menu')
@include('partials.menu.point_exchange_menu', ['currentRoute' => 'exchange_infos.index'])
@endsection

@section('content')
<h2>交換先一覧</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions">操作</th>
        <th>交換先</th>
        <th>状態</th>
        <th>円交換比率</th>
        <th>期間</th>
        <th>メッセージ</th>
    </tr>
    @php
    $exchange_map = config('exchange.point');
    $status_map = [
        0 => ['class' => 'active', 'status' => '公開'],
        1 => ['class' => 'warning', 'status' => '停止'],
    ];
    @endphp

    @foreach ($exchange_map as $exchange_type => $exchange)
    @php
    $exchange_info = $exchange_info_map[$exchange_type] ?? null;
    $status = $exchange_info->status ?? 1;
    @endphp
    <tr class="{{ $status_map[$status]['class'] }}">
        <td>
            @if ($status != 2)
            {{ Tag::link(route('exchange_infos.show', ['type' => $exchange_type]), '詳細', ['class' => 'btn btn-small btn-info']) }}<br />
            @endif
        </td>
        <td>{{ $exchange['label'] }}</td>
        <td>{{ $status_map[$status]['status'] }}</td>
        @if (isset($exchange_info))
        <td>{{ $exchange_info->yen_rate }}%</td>
        <td>
            {{ $exchange_info->start_at->format('Y-m-d H:i') }}～
            @if (!$exchange_info->stop_at->eq(\Carbon\Carbon::parse('9999-12-31')->endOfMonth()))
            {{ $exchange_info->stop_at->format('Y-m-d H:i') }}
            @endif
        </td>
        <td>{!! nl2br(e($exchange_info->message_body)) !!}</td>
        @else
        <td></td><td></td><td></td>
        @endif
    </tr>
    @endforeach
</table>
@endsection
