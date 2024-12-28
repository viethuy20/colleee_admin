@extends('layouts.master')

@section('title', 'ポイント先出し成果管理')

@section('head.load')
<script type="text/javascript"><!--
$(function() {
    $('#PreAffRewardsUnblockButton').on('click', function() {
        $('#PreAffRewardsUnblockForm').submit();
    });
});
//-->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('users.edit', ['user' => $user]), 'ユーザー更新') }}</li>
<li>{{ Tag::link(route('users.point_history', ['user' => $user]), 'ポイント履歴') }}</li>
<li>{{ Tag::link(route('users.login_history', ['user' => $user]), 'ログイン履歴') }}</li>
<li class="active">{{ Tag::link(route('pre_aff_rewards.list', ['user' => $user]), 'ポイント先出し成果一覧') }}</li>
@endsection

@php
$pre_reward_status_map = config('reward.pre_status');
@endphp

@section('content')
<h2>ユーザー情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>ポイント</th><th>ポイント先出し状態</th></tr>
    <tr>
        <td>{{ $user->name }}</td>
        <td>{{ number_format($user->point) }}</td>
        <td>
            @if (\App\Buser::isBlocked(\App\Buser::PRE_REWARD_TYPE, $user->id))
            <a id="PreAffRewardsUnblockButton">ブロック中</a>
            {{ Tag::formOpen(['url' => route('pre_aff_rewards.unblock'), 'onsubmit' => "return confirm('このユーザーのブロックを解除しますか?');", 'id' => 'PreAffRewardsUnblockForm']) }}
            @csrf    
            {{ Tag::formHidden('user_id', $user->id) }}
            {{ Tag::formClose() }}
            @else
            未ブロック
            @endif
        </td>
    </tr>
</table>

<h2>ポイント先出し成果</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th rowspan="2">ID</th>
        <th>ユーザーID</th>
        <th>プログラムID</th>
        <th colspan="2">名称</th>
        <th>配付ポイント</th>
        <th>発生日時</th>
    </tr>
    <tr>
        <th>ASP</th>
        <th>ASP側広告ID</th>
        <th>注文番号</th>
        <th>状態</th>
        <th>損害ポイント</th>
        <th>受け付け日時</th>
    </tr>
    @forelse ($paginator as $index => $pre_aff_reward)
    @php
    $aff_reward = $pre_aff_reward->aff_reward;
    $user = $aff_reward->user;
    $affiriate = $aff_reward->affiriate;
    @endphp
    <tr>
        <td rowspan="2">{{ $aff_reward->id }}&nbsp;</td>
        <td>{{ $user->name ?? \App\User::getNameById($aff_reward->user_id) }}&nbsp;</td>
        <td>{{ (isset($affiriate->id) && $affiriate->parent_type == App\Affiriate::PROGRAM_TYPE && $affiriate->parent_id > 0) ? $affiriate->parent_id : 'なし' }}&nbsp;</td>
        <td colspan="2">{{ $aff_reward->title }}&nbsp;</td>
        <td>{{ $aff_reward->point }}&nbsp;</td>
        <td>{{ isset($aff_reward->actioned_at) ? $aff_reward->actioned_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    <tr>
        <td>{{ $asp_map[$aff_reward->asp_id] ?? '不明:'.$aff_reward->asp_id }}&nbsp;</td>
        <td>{{ $aff_reward->asp_affiriate_id }}&nbsp;</td>
        <td>{{ $aff_reward->order_id }}&nbsp;</td>
        <td>{{ $pre_reward_status_map[$pre_aff_reward->status] ?? '' }}&nbsp;</td>
        <td>{{ $pre_aff_reward->damage_point > 0 ? $pre_aff_reward->damage_point : '' }}&nbsp;</td>
        <td>{{ isset($aff_reward->created_at) ? $aff_reward->created_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="7">成果は存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
