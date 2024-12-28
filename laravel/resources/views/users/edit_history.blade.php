@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li>{{ Tag::link(route('users.edit', ['user' => $user]), 'ユーザー更新') }}</li>
<li>{{ Tag::link(route('users.point_history', ['user' => $user]), 'ポイント履歴') }}</li>
<li>{{ Tag::link(route('users.login_history', ['user' => $user]), 'ログイン履歴') }}</li>
<li class="active">{{ Tag::link(route('users.edit_history', ['user' => $user]), '更新履歴') }}</li>
@endsection

@section('content')
<h2>ユーザー情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>ポイント</th></tr>
    <tr><td>{{ $user->name }}</td><td>{{ number_format($user->point) }}</td></tr>
</table>

<h2>更新履歴</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>更新日時</th>
        <th>更新項目</th>
        <th>更新内容</th>
        <th>IP</th>
        <th>ユーザーエージェント</th>
    </tr>
    @php
    $user_edit_type_map = config('map.use_edit_type');

    $edit_log_list = $user->edit_logs()->orderBy('id', 'asc')->get();
    @endphp
    @forelse ($edit_log_list as $edit_log)
    <tr>
        <td>{{ $edit_log->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $user_edit_type_map[$edit_log->type] ?? '不明' }}&nbsp;</td>
        <td>
            @switch($edit_log->type)
            @case(\App\UserEditLog::INIT_TYPE)
            @case(\App\UserEditLog::EMAIL_TYPE)
            @case(\App\UserEditLog::EMAIL_REMIND_TYPE)
            {{ $edit_log->email }}<br />
            @break
            @endswitch
            @switch($edit_log->type)
            @case(\App\UserEditLog::INIT_TYPE)
            @case(\App\UserEditLog::TEL_TYPE)
            {{ $edit_log->tel }}<br />
            @break
            @endswitch
        </td>
        <td>{{ $edit_log->ip }}&nbsp;</td>
        <td>{{ $edit_log->ua }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="5">更新履歴は存在しません</td></tr>
    @endforelse
</table>

@endsection
