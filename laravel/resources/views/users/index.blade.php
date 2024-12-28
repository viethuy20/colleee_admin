@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('menu')
<li class="active">{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li>{{ Tag::link(route('users.csv'), 'ユーザーアカウント管理') }}</li>
<li>{{ Tag::link(route('users.kpi'), 'KPI') }}</li>
@php
$users_export_url = route('users.export_csv');
$query = $paginator->getQuery();
if (!empty($query)) {
    $users_export_url = $users_export_url.'?'.\Illuminate\Support\Arr::query($query);
}
@endphp
<li>{{ Tag::link($users_export_url, 'CSVエクスポート') }}</li>
@endsection

@php
$status_map = config('map.user_status');
@endphp

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('users.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="UserName">ID</label>
        {{ Tag::formText('user_name', $paginator->getQuery('user_name') ?? '', ['class' => 'form-control', 'id' => 'UserName']) }}
    </div>
    <div class="form-group">
        <label for="UserEmail">メールアドレス</label>
        {{ Tag::formText('email', $paginator->getQuery('email') ?? '', ['class' => 'form-control', 'id' => 'UserEmail']) }}
    </div>
    <div class="form-group">
        <label for="UserStatus">状態</label>
        {{ Tag::formSelect('status', ['' => '---'] + $status_map, $paginator->getQuery('status') ?? '', ['class' => 'form-control', 'id' => 'UserStatus']) }}
    </div>
    <div class="form-group">
        <label for="UserFriendCode">フレンドコード</label>
        {{ Tag::formText('friend_code', $paginator->getQuery('friend_code') ?? '', ['class' => 'form-control', 'id' => 'UserFriendCode']) }}
    </div>
    <div class="form-group">
        <label for="UserFriendUserName">紹介元ユーザーID</label>
        {{ Tag::formText('friend_user_name', $paginator->getQuery('friend_user_name') ?? '', ['class' => 'form-control', 'id' => 'UserFriendUserName']) }}
    </div>
    <div class="form-group">
        <label for="UserTel">電話番号</label>
        {{ Tag::formText('tel', $paginator->getQuery('tel') ?? '', ['class' => 'form-control', 'id' => 'UserTel']) }}
    </div>
    <div class="form-group">
        <label for="UserIp">IPアドレス</label>
        {{ Tag::formText('ip', $paginator->getQuery('ip') ?? '', ['class' => 'form-control', 'id' => 'UserIp']) }}
    </div>
    <div class="form-group">
        <label for="FancrewUserId">FancrewのユーザーID</label>
        {{ Tag::formText('fancrew_user_id', $paginator->getQuery('fancrew_user_id') ?? '', ['class' => 'form-control', 'id' => 'FancrewUserId']) }}
    </div>
    <div class="form-group">
        <label for="StartCreatedAt">入会期間</label>
        <div class="form-inline">
            {{ Tag::formText('start_created_at', $paginator->getQuery('start_created_at') ?? '', ['class' => 'form-control', 'id' => 'StartCreatedAt']) }}
            ～
            {{ Tag::formText('end_created_at', $paginator->getQuery('end_created_at') ?? '', ['class' => 'form-control']) }}
        </div>
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>ユーザー</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions" rowspan="2">操作</th><th>ユーザーID</th><th>ステータス</th>
        <th>紹介元ユーザーID</th><th>メールアドレス</th><th>現在のポイント</th><th>登録日時</th>
    </tr>
    <tr><th>予想ユーザーID</th><th colspan="2">フレンドコード</th><th>IPアドレス</th><th>交換累計ポイント</th><th>アクション日時</th></tr>
    @php
    $now = Carbon\Carbon::now();
    $class_map = [\App\User::COLLEEE_STATUS => 'active', \App\User::FORCE_WITHDRAWAL_STATUS => 'danger'];
    @endphp
    @forelse ($paginator as $index => $user)
    <tr class="{{ $class_map[$user->status] ?? 'warning' }}">
        <td class="actions" style="white-space:nowrap" rowspan="2">
            {{ Tag::link(route('users.edit', ['user' => $user]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            {{ Tag::link(route('users.point_history', ['user' => $user]), 'ポイント履歴', ['class' => 'btn btn-small btn-info']) }}<br />
            {{ Tag::link(route('users.login_history', ['user' => $user]), 'ログイン履歴', ['class' => 'btn btn-small btn-info']) }}<br />
            {{ Tag::link(route('users.edit_history', ['user' => $user]), '更新履歴', ['class' => 'btn btn-small btn-info']) }}<br />
            @if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
            {{ Tag::link(route('banks.account_list', ['user' => $user]), '銀行口座一覧', ['class' => 'btn btn-small btn-info']) }}<br />
            @endif
        </td>
        <td>{{ $user->name }}&nbsp;</td>
        <td>{{ $status_map[$user->status] }}&nbsp;</td>
        <td>{{ $user->friend_user->name ?? '' }}&nbsp;</td>
        <td>{{ $user->email }}&nbsp;</td>
        <td>{{ number_format($user->point) }}&nbsp;</td>
        <td>{{ $user->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    <tr class="{{ $class_map[$user->status] ?? 'warning' }}">
        <td>{{ $user->old_id ?? '' }}&nbsp;</td>
        <td colspan="2">{{ $user->friend_code }}&nbsp;</td>
        <td>{{ $user->ip }}&nbsp;</td>
        <td>{{ number_format($user->exchanged_point) }}&nbsp;</td>
        <td>{{ isset($user->actioned_at) ? $user->actioned_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="7">ユーザーは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
