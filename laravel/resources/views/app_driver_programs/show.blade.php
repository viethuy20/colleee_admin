@extends('layouts.master')

@section('title', 'プログラム管理')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li>{{ Tag::link(route('app_driver_programs.index'), 'AppDriverプログラム一覧') }}</li>
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

@php
$device_map = \App\External\AppDriver::$DEVICE_MAP;
$budget_is_unlimited_map = \App\External\AppDriver::$BUDGET_IS_UNLIMITED_MAP;
$subscription_duration_map = \App\External\AppDriver::$SUBSCRIPTION_DURATION_MAP;
$market_map = \App\External\AppDriver::$MARKET_MAP;
$duplication_type_map = \App\External\AppDriver::$DUPLICATION_TYPE_MAP;
$requisite_map = \App\External\AppDriver::$REQUISITE_MAP;
@endphp
{{ Tag::formOpen(['url' => route('app_driver_programs.create'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($affiriate))
        <legend>プログラム更新</legend>
        @else
        <legend>新規プログラム作成</legend>
        @endif
        <div class="form-group">
            <label>プロモーションID</label><br />
            {{ $campaign['id'] }}
            {{ Tag::formHidden('id', old('id', $campaign['id'] ?? '')) }}
        </div>
        <div class="form-group">
            <label for="CampaignName">プロモーション名</label><br />
            {{ Tag::formText('name', old('name', $campaign['name'] ?? ''), ['class' => 'form-control', 'id' => 'CampaignName']) }}<br />
            @if (isset($affiriate))
            修正前:{{ $affiriate->program->title }}<br />
            @endif
        </div>
        <div class="form-group">
            <label for="CampaignLocation">プロモーションURL</label><br />
            {{ $campaign['location'] }}<br />
            {{ Tag::formHidden('location', old('location', $campaign['location'] ?? '')) }}
            @if (isset($affiriate))
            修正前:{{ $affiriate->url }}<br />
            @endif
        </div>
        <div class="form-group">
            <label for="CampaignRemark">プロモーション注意事項</label><br />
            {{ Tag::formTextarea('remark', old('remark', $campaign['remark'] ?? null), ['class' => 'form-control', 'rows' => 10, 'id' => 'CampaignRemark']) }}<br />
            @if (isset($affiriate))
            修正前:{{ $affiriate->program->schedule[0]->reward_condition }}<br />
            @endif
        </div>
        <div class="form-group">
            <label>配信開始⽇～配信終了⽇</label><br />
            {{ Tag::formHidden('start_time', old('start_time', $campaign['start_time'] ?? '')) }}
            {{ Tag::formHidden('end_time', old('end_time', $campaign['end_time'] ?? '')) }}
            <div class="form-inline">
                {{ \Carbon\Carbon::parse($campaign['start_time'])->format('Y-m-d H:i') }}～
                {{ \Carbon\Carbon::parse($campaign['end_time'])->format('Y-m-d H:i') }}
            </div>
            @if (isset($affiriate))
            <div class="form-inline">
                修正前:{{ $affiriate->program->start_at->format('Y-m-d H:i') }}～
                {{ $affiriate->program->stop_at->format('Y-m-d H:i') }}
            </div>
            @endif
        </div>
        <div class="form-group">
            <label>残予算の有無</label><br />
            {{ $budget_is_unlimited_map[$campaign['budget_is_unlimited']] ?? '不明' }}
        </div>
        <div class="form-group">
            <label for="CampaignDetail">詳細</label><br />
            {{ Tag::formTextarea('detail', old('detail', $campaign['detail'] ?? null), ['class' => 'form-control', 'rows' => 5, 'id' => 'CampaignDetail']) }}<br />
            @if (isset($affiriate))
            修正前:{!! $affiriate->program->detail !!}<br />
            @endif
        </div>
        <div class="form-group">
            <label for="CampaignIcon">アイコンURL</label><br />
            {{ Tag::image($campaign['icon'], 'img') }}
            {{ Tag::formText('icon', old('icon', $campaign['icon'] ?? null), ['class' => 'form-control fileUrl', 'maxlength' => '256', 'id' => 'CampaignIcon',]) }}<br />
            @if (isset($affiriate))
            {{ Tag::image($affiriate->img_url, 'img') }}
            修正前:{{ $affiriate->img_url }}<br />
            @endif
        </div>
        <div class="form-group">
            <label>サイト/アプリURL</label><br />
            {{ $campaign['url'] }}
        </div>
        <div class="form-group">
            <label>プラットフォーム</label><br />
            {{ $device_map[$campaign['platform']] ?? '不明' }}
            {{ Tag::formHidden('platform', old('platform', $campaign['platform'] ?? '')) }}
            @if (isset($affiriate))
            修正前:
            @foreach ($affiriate->program->device as $device_id)
            {{ $device_map[$device_id] }}
            @if (!$loop->last)
            ,
            @endif
            @endforeach
            <br />
            @endif
        </div>
        <div class="form-group">
            <label>マーケット</label><br />
            {{ $market_map[$campaign['market']] ?? 'マーケットなし' }}
        </div>
        <div class="form-group">
            <label>アプリ価格</label><br />
            {{ ($campaign['price'] == 0) ? '無料' : number_format($campaign['price']) }}
        </div>
        <div class="form-group">
            <label>⽉額料⾦</label><br />
            {{ $subscription_duration_map[$campaign['subscription_duration']] }}
        </div>
        <div class="form-group">
            <label>残件数</label><br />
            {{ (is_numeric($campaign['remaining'])) ? number_format($campaign['remaining']) : '' }}
        </div>
        <div class="form-group">
            <label>重複タイプ</label><br />
            {{ $duplication_type_map[$campaign['duplication_type']] ?? '不明' }}
        </div>

        @php
        $advertisement_total = WrapPhp::count($campaign['advertisement']);
        @endphp
        @if ($advertisement_total > 0)
        <legend>成果地点</legend>
        <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
            <tr>
                <th>サンクスID</th>
                <th>サンクス名</th>
                <th>サンクスカテゴリ</th>
                <th>認証期間</th>
                <th>媒体報酬</th>
            </tr>
            @for ($i = 0; $i < $advertisement_total; $i++)
            @php
            $advertisement = $campaign['advertisement'][$i];
            @endphp
            <tr>
                <td>{{ $advertisement['id'] }}</td>
                <td>{{ $advertisement['name'] }}</td>
                <td>{{ $requisite_map[$advertisement['requisite']] ?? '不明' }}</td>
                <td>{{ number_format($advertisement['period']) }}</td>
                <td>{{ number_format($advertisement['payment']) }}</td>
            </tr>
            @endfor
        </table>
        @endif

        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
