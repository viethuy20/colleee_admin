@extends('layouts.master')

@section('title', 'AppDriverプログラム管理')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li class="active">{{ Tag::link(route('app_driver_programs.index'), 'AppDriverプログラム一覧') }}</li>
<li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $sp_program_type)
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
@endforeach
@endsection

@section('content')
<h2>AppDriverプログラム</h2>
@php
$device_map = \App\External\AppDriver::$DEVICE_MAP;
$budget_is_unlimited_map = \App\External\AppDriver::$BUDGET_IS_UNLIMITED_MAP;
$subscription_duration_map = \App\External\AppDriver::$SUBSCRIPTION_DURATION_MAP;
$market_map = \App\External\AppDriver::$MARKET_MAP;
$duplication_type_map = \App\External\AppDriver::$DUPLICATION_TYPE_MAP;
$campaign_total = WrapPhp::count($app_driver_response->campaign);
@endphp

更新日時:{{ $app_driver_response->last_update->format('Y-m-d H:i:s') }}<br />
総件数:{{ number_format($campaign_total) }}<br />
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions" rowspan="2">操作</th>
        <th>プロモーションID</th>
        <th>プロモーション名</th>
        <th>アイコン</th>
        <th>{{ Tag::link(route('app_driver_programs.index').'?'.http_build_query(['sort' => (1 == abs($sort) && $sort > 0) ? -1 : 1]), 'プラットフォーム') }}</th>
        <th>{{ Tag::link(route('app_driver_programs.index').'?'.http_build_query(['sort' => (2 == abs($sort) && $sort > 0) ? -2 : 2]), 'アプリ価格') }}</th>
        <th>配信開始⽇</th>
    </tr>
    <tr>
        <th>残予算の有無</th>
        <th>マーケット</th>
        <th>残件数</th>
        <th>重複タイプ</th>
        <th>{{ Tag::link(route('app_driver_programs.index').'?'.http_build_query(['sort' => (3 == abs($sort) && $sort > 0) ? -3 : 3]), '月額料金') }}</th>
        <th>配信終了⽇</th>
    </tr>
    @if ($campaign_total > 0)
    @foreach ($app_driver_response->campaign as $campaign)
    <tr>
        <td rowspan="2">
            {{ Tag::formOpen(['url' => route('app_driver_programs.show', ['app_driver_program' => $campaign->id])]) }}
            @csrf    
            {{ Tag::formHidden('name', $campaign->name) }}
                {{ Tag::formHidden('location', $campaign->location) }}
                {{ Tag::formHidden('remark', $campaign->remark) }}
                {{ Tag::formHidden('start_time', $campaign->start_time->format('Y-m-d H:i:s')) }}
                {{ Tag::formHidden('end_time', $campaign->end_time->format('Y-m-d H:i:s')) }}
                {{ Tag::formHidden('budget_is_unlimited', $campaign->budget_is_unlimited) }}
                {{ Tag::formHidden('detail', $campaign->detail) }}
                {{ Tag::formHidden('icon', $campaign->icon) }}
                {{ Tag::formHidden('url', $campaign->url) }}
                {{ Tag::formHidden('platform', $campaign->platform) }}
                {{ Tag::formHidden('market', $campaign->market) }}
                {{ Tag::formHidden('price', $campaign->price) }}
                {{ Tag::formHidden('subscription_duration', $campaign->subscription_duration) }}
                {{ Tag::formHidden('remaining', $campaign->remaining) }}
                {{ Tag::formHidden('duplication_type', $campaign->duplication_type) }}
                @php
                $advertisement_total = WrapPhp::count($campaign->advertisement);
                @endphp
                @foreach ($campaign->advertisement as $i => $advertisement)
                @php
                $html_name = 'advertisement['.$i.']';
                @endphp
                {{ Tag::formHidden($html_name.'[id]', $advertisement->id) }}
                {{ Tag::formHidden($html_name.'[name]', $advertisement->name) }}
                {{ Tag::formHidden($html_name.'[requisite]', $advertisement->requisite) }}
                {{ Tag::formHidden($html_name.'[period]', $advertisement->period) }}
                {{ Tag::formHidden($html_name.'[payment]', $advertisement->payment) }}
                {{ Tag::formHidden($html_name.'[point]', $advertisement->point) }}
                @endforeach
                {{ Tag::formSubmit('参照', ['class' => 'btn btn-small btn-success']) }}
            {{ Tag::formClose() }}
        </td>
        <td>{{ $campaign->id }}</td>
        <td>{{ $campaign->name }}</td>
        <td>{{ Tag::image($campaign->icon, $campaign->name, ['width' => '120px']) }}</td>
        <td>{{ $device_map[$campaign->platform] ?? '不明' }}</td>
        <td>{{ ($campaign->price == 0) ? '無料' : number_format($campaign->price) }}</td>
        <td>{{ $campaign->start_time->format('Y-m-d H:i') }}</td>
    </tr>
    <tr>
        <td>{{ $budget_is_unlimited_map[$campaign->budget_is_unlimited] ?? '不明' }}</td>
        <td>{{ $market_map[$campaign->market] ?? 'マーケットなし' }}</td>
        <td>{{ (is_numeric($campaign->remaining)) ? number_format($campaign->remaining) : '' }}</td>
        <td>{{ $duplication_type_map[$campaign->duplication_type] ?? '不明' }}</td>
        <td>{{ $subscription_duration_map[$campaign->subscription_duration] }}</td>
        <td>{{ $campaign->end_time->format('Y-m-d H:i') }}</td>
    </tr>
    @endforeach
    @endif
</table>

@endsection
