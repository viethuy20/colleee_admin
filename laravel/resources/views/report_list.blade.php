@extends('layouts.master')

@section('title', 'レポート')

@section('menu')
@for ($i = 0; $i < 3; $i++)
@php
$t = \Carbon\Carbon::today()->startOfMonth()->subMonths($i);
@endphp
<li{!! $t->eq($target) ? ' class="active"' : '' !!}>{{ Tag::link(route('reports.list', ['ym' => $t->format('Ym')]), $t->format('n').'月レポート') }}</li>
@endfor
@php
$currentRoute = \Request::route()->getName();
$classActive = '';
if ($currentRoute == 'reports.monthly') {
    $classActive = 'class="active"';
}
@endphp
<li><a {{ $classActive }} href="{{ route('reports.monthly') }}">ポイント推移レポート</a></li>
<li>
    <a href="{{ route('reports.user_link_fanspot') }}">FanSpot連携会員情報</a>
</li>
<li>
    <a href="{{ route('reports.user_link_cp') }}">ドットマネーCP会員情報</a>
</li>
@endsection

@section('content')
<h2>{{ $target->format('n') }}月交換ポイントレポート</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    @php
    $report_map = config('report.map');
    $sum_map = [];
    @endphp
    <tr>
        <th>対象日</th>
        @foreach($report_map as $title)
        <th>{{ $title }}</th>
        @endforeach
    </tr>
    @forelse ($report_list as $report)
    <tr{!! $report->target_at->isWeekend() ? ' class="bg-warning"' : '' !!}>
        @php
        $data = json_decode($report->data) ?? null;
        @endphp
        <td>{{ $report->target_date }}&nbsp;</td>
        @foreach($report_map as $key => $title)
        @php
        $value = $data->{$key} ?? 0;
        $sum_map[$key] = ($sum_map[$key] ?? 0) + $value;
        @endphp
        <td>{{ number_format($value).'('.number_format($sum_map[$key]).')' }}&nbsp;</td>
        @endforeach
    </tr>
    @empty
    <tr><td colspan="{{ (1 + WrapPhp::count($report_map)) }}">レポートは存在しません</td></tr>
    @endforelse
</table>
@endsection
