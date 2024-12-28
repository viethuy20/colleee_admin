@php
$exchange = config('exchange.point.'.$type);
@endphp
@extends('layouts.master')

@section('title', $exchange['label'])

@section('menu')
@include('partials.menu.point_exchange_menu', ['currentRoute' => 'exchange_infos.index'])
<li>{{ Tag::link(route('exchange_infos.create', ['type' => $type]), '追加') }}</li>
@endsection

@section('content')
<h2>{{ $exchange['label'] }}</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions">操作</th>
        <th>状態</th>
        <th>円交換比率</th>
        <th>期間</th>
    </tr>
    @php
    $show_map = [0 => '公開', 1 => '停止'];
    @endphp
    @forelse ($exchange_info_list as $exchange_info)
    <tr>
        <td>
            @if (!$exchange_info->stopped)
            {{ Tag::link(route('exchange_infos.edit', ['exchange_info' => $exchange_info]), '編集', ['class' => 'btn btn-small btn-success']) }}
            @endif
        </td>
        <td>{{ $show_map[$exchange_info->status] }}</td>
        <td>{{ $exchange_info->yen_rate }}%</td>
        <td>
            {{ $exchange_info->start_at->format('Y-m-d H:i') }}～
            @if (!$exchange_info->stop_at->eq(\Carbon\Carbon::parse('9999-12-31')->endOfMonth()))
            {{ $exchange_info->stop_at->format('Y-m-d H:i') }}
            @endif    
        </td>
    </tr>
    @empty
    <tr><td colspan="4">交換情報は存在しません</td></tr>
    @endforelse
</table>
@endsection
