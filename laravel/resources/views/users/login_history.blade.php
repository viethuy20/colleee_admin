@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li>{{ Tag::link(route('users.edit', ['user' => $user]), 'ユーザー更新') }}</li>
<li>{{ Tag::link(route('users.point_history', ['user' => $user]), 'ポイント履歴') }}</li>
<li class="active">{{ Tag::link(route('users.login_history', ['user' => $user]), 'ログイン履歴') }}</li>
<li>{{ Tag::link(route('users.edit_history', ['user' => $user]), '更新履歴') }}</li>
@endsection

@section('content')
<h2>ユーザー情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>ポイント</th></tr>
    <tr><td>{{ $user->name }}</td><td>{{ number_format($user->point) }}</td></tr>
</table>

<h2>ログイン履歴</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>ログイン日時</th>
        <th>IP</th>
        <th>ユーザーエージェント</th>
        <th>端末</th>
    </tr>
    <?php $device_map = config('map.device'); ?>
    @forelse ($user_login_list as $user_login)
    <tr>
        <td>{{ $user_login->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $user_login->ip }}&nbsp;</td>
        <td>{{ $user_login->ua }}&nbsp;</td>
        <td>{{ $device_map[$user_login->device_id] }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="4">ログイン履歴は存在しません</td></tr>
    @endforelse
</table>

@endsection
