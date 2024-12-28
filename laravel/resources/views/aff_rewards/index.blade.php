@extends('layouts.master')

@section('title', '成果管理')

@section('menu')
<li>{{ Tag::link(route('external_links.index'), 'クリック一覧') }}</li>
<li class="active">{{ Tag::link(route('aff_rewards.index'), '成果一覧') }}</li>
@php
$aff_rewards_export_url = route('aff_rewards.export_csv');
$query = $paginator->getQuery();
if (!empty($query)) {
    $aff_rewards_export_url = $aff_rewards_export_url.'?'.\Illuminate\Support\Arr::query($query);
}
@endphp
<li>{{ Tag::link($aff_rewards_export_url, 'CSVエクスポート') }}</li>
@if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
<li>{{ Tag::link(route('aff_rewards.import'), '成果インポート') }}</li>
<li>{{ Tag::link(route('aff_rewards.achievement'), '成果レポート') }}</li>
@endif
@endsection

@php
$reward_status_map = config('reward.status');
$reward_error_map = config('reward.error');
@endphp

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('aff_rewards.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="RewardUserName">ユーザーID</label>
        {{ Tag::formText('user_name', $paginator->getQuery('user_name') ?? '', ['class' => 'form-control', 'id' => 'RewardUserName']) }}
    </div>
    <div class="form-group">
        <label for="ProgramId">プログラムID</label>
        {{ Tag::formText('program_id', $paginator->getQuery('program_id') ?? '', ['class' => 'form-control', 'id' => 'ProgramId']) }}
    </div>
    <div class="form-group">
        <label for="RewardAspId">ASP</label>
        {{ Tag::formSelect('asp_id', ['' => '---'] + $asp_map, $paginator->getQuery('asp_id') ?? '', ['class' => 'form-control', 'id' => 'RewardAspId']) }}
    </div>
    <div class="form-group">
        <label for="RewardAspAffiliateId">データ連携ID</label>
        {{ Tag::formText('asp_affiliate_id', $paginator->getQuery('asp_affiliate_id') ?? '', ['class' => 'form-control', 'id' => 'RewardAspAffiliateId']) }}
    </div>
    <div class="form-group">
        <label for="RewardOrderId">注文番号</label>
        {{ Tag::formText('order_id', $paginator->getQuery('order_id') ?? '', ['class' => 'form-control', 'id' => 'RewardOrderId']) }}
    </div>
    <div class="form-group">
        <label for="RewardStatus">状態</label>
        {{ Tag::formSelect('status', ['' => '---'] + $reward_status_map, $paginator->getQuery('status') ?? '', ['class' => 'form-control', 'id' => 'RewardStatus']) }}
    </div>
    <div class="form-group">
        <label for="RewardCode">エラーコード</label>
        {{ Tag::formSelect('code', ['' => '---'] + $reward_error_map, $paginator->getQuery('code') ?? '', ['class' => 'form-control', 'id' => 'RewardCode']) }}
    </div>
    <div class="form-group">
        <label for="RewardStartActionedAt">発生期間</label>
        <div class="form-inline">
            {{ Tag::formText('start_actioned_at', $paginator->getQuery('start_actioned_at') ?? '', ['class' => 'form-control', 'id' => 'RewardStartActionedAt']) }}
            ～
            {{ Tag::formText('end_actioned_at', $paginator->getQuery('end_actioned_at') ?? '', ['class' => 'form-control']) }}
        </div>
    </div>
    <div class="form-group">
        <label for="RewardStartCreatedAt">受け付け期間</label>
        <div class="form-inline">
            {{ Tag::formText('start_created_at', $paginator->getQuery('start_created_at') ?? '', ['class' => 'form-control', 'id' => 'RewardStartCreatedAt']) }}
            ～
            {{ Tag::formText('end_created_at', $paginator->getQuery('end_created_at') ?? '', ['class' => 'form-control']) }}
        </div>
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>成果</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th rowspan="2">ID</th>
        <th>ユーザーID</th>
        <th>プログラムID</th>
        <th colspan="2">名称<br>(コース名)</th>
        <th>状態</th>
        <th>ポイント</th>
        <th>ポイント<br />+<br />ボーナス</th>
        <th>発生日時</th>
        <th rowspan="2">データ</th>
    </tr>
    <tr>
        <th>予想ユーザーID</th>
        <th>ASP</th>
        <th>データ連携ID</th>
        <th>注文番号<br/>(コースID)</th>
        <th>エラー</th>
        <th>ボーナス</th>
        <th>報酬額</th>
        <th>受け付け日時</th>
    </tr>
    @forelse ($paginator as $index => $aff_reward)
    @php
    $user = $aff_reward->user;
    $affiriate = $aff_reward->affiriate;
    @endphp
    <tr>
        <td rowspan="2">{{ $aff_reward->id }}&nbsp;</td>
        <td>{{ $user->name ?? \App\User::getNameById($aff_reward->user_id) }}&nbsp;</td>
        <td>{{ (isset($affiriate->id) && $affiriate->parent_type == App\Affiriate::PROGRAM_TYPE && $affiriate->parent_id > 0) ? $affiriate->parent_id : 'なし' }}&nbsp;</td>
        <td colspan="2" style="word-break: break-all;">{{ $aff_reward->title }}&nbsp;<br/>{{ !is_null($aff_reward->course_name) ? "($aff_reward->course_name)" : ''}}&nbsp;</td>
        <td>{{ $reward_status_map[$aff_reward->status] ?? '' }}&nbsp;</td>
        <td>{{ $aff_reward->diff_point }}&nbsp;</td>
        <td>{{ $aff_reward->point }}&nbsp;</td>
        <td>{{ isset($aff_reward->actioned_at) ? $aff_reward->actioned_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
        <td rowspan="2">{{ $aff_reward->data }}&nbsp;</td>
    </tr>
    <tr>
        <td>{{ $user->old_id ?? '' }}&nbsp;</td>
        <td>{{ $asp_map[$aff_reward->asp_id] ?? '不明:'.$aff_reward->asp_id }}&nbsp;</td>
        <td>{{ $aff_reward->asp_affiriate_id }}&nbsp;</td>
        <td style="word-break: break-all;">{{ $aff_reward->order_id }}&nbsp;<br/>{{ !is_null($aff_reward->aff_course_id) ? "($aff_reward->aff_course_id)" : ''}}&nbsp;</td>
        <td>{{ $reward_error_map[$aff_reward->code] ?? '' }}&nbsp;</td>
        <td>{{ $aff_reward->bonus_point }}&nbsp;</td>
        <td>{{ $aff_reward->reward_amount }}</td>
        <td>{{ isset($aff_reward->created_at) ? $aff_reward->created_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="10">成果は存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
