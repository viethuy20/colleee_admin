@extends('layouts.master')

@section('title', 'レポート')

@section('menu')
    @for ($i = 0; $i < 3; $i++)
        @php
            $t = \Carbon\Carbon::today()
                ->startOfMonth()
                ->subMonths($i);
        @endphp
        <li>{{ Tag::link(route('reports.list', ['ym' => $t->format('Ym')]), $t->format('n') . '月レポート') }}</li>
    @endfor
    @php
        $currentRoute = \Request::route()->getName();
        $classActive = '';
        $classFanspotActive = '';
        $classCpActive = '';
        
        switch ($currentRoute) {
            case 'reports.monthly':
                $classActive = 'class=active';
                break;
        
            case 'reports.user_link_fanspot':
                $classFanspotActive = 'class=active';
                break;
        
            case 'reports.user_link_cp':
                $classCpActive = 'class=active';
                break;
        
            default:
                break;
        }
    @endphp
    <li {{ $classActive }}><a href="{{ route('reports.monthly') }}">ポイント推移レポート</a></li>
    <li {{ $classFanspotActive }}>
        <a href="{{ route('reports.user_link_fanspot') }}">FanSpot連携会員情報</a>
    </li>
    <li {{ $classCpActive }}>
        <a href="{{ route('reports.user_link_cp') }}">ドットマネーCP会員情報</a>
    </li>
@endsection

@section('content')
<h2>csvダウンロード</h2>
<a class="btn btn-default" target="_blank" href="{{ route('reports.csv_monthly') }}">取得</a>
<div style="margin-top: 10px">
    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>対象月</th>
                <th>ポイント残高</th>
                <th>新規発生ポイント高</th>
                <th>新規確定ポイント高</th>
                <th>交換ポイント高</th>
                <th>失効ポイント高</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ date('Y-m-d', strtotime($item->report_day)) }}</td>
                <td>{{ number_format($item->sum_balance_point) }}</td>
                <td>{{ number_format($item->sum_action_point) }}</td>
                <td>{{ number_format($item->sum_confirm_point) }}</td>
                <td>{{ number_format($item->sum_exchange_point) }}</td>
                <td>{{ number_format($item->sum_lost_point) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>    
</div>
@endsection
