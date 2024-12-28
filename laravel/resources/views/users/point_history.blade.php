@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li>{{ Tag::link(route('users.edit', ['user' => $user]), 'ユーザー更新') }}</li>
<li class="active">{{ Tag::link(route('users.point_history', ['user' => $user]), 'ポイント履歴') }}</li>
<li>{{ Tag::link(route('users.login_history', ['user' => $user]), 'ログイン履歴') }}</li>
<li>{{ Tag::link(route('users.edit_history', ['user' => $user]), '更新履歴') }}</li>
@endsection

@section('content')
<h2>ユーザー情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>所持ポイント</th><th>交換ポイント</th></tr>
    <tr><td>{{ $user->name }}</td><td>{{ number_format($user->point) }}</td><td>{{ number_format($user->exchanged_point) }}</td></tr>
</table>

<h2>ポイント履歴</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>ID</th>
        <th>登録日時</th>
        <th>種類</th>
        <th>プログラムID</th>
        <th>名称</th>
        <th>基本ポイント</th>
        <th>ボーナスポイント</th>
        <th>変動ポイント</th>
    </tr>
    @php
    $point_type_map = config('map.point_type');
    @endphp
    @forelse ($paginator as $user_point)
    <tr>
        <td>{{ $user_point->id }}&nbsp;</td>
        <td>{{ $user_point->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $point_type_map[$user_point->type] ?? '不明' }}&nbsp;</td>
        <td>
            @if ($user_point->type == App\UserPoint::PROGRAM_TYPE || $user_point->type == App\UserPoint::SP_PROGRAM_TYPE)
            {{ $user_point->parent_id }}
            @endif
            &nbsp;
        </td>
        <td>{{ $user_point->title }}&nbsp;</td>
        <td>{{ number_format($user_point->diff_point) }}&nbsp;</td>
        <td>{{ number_format($user_point->bonus_point) }}&nbsp;</td>
        <td>{{ number_format($user_point->diff_point + $user_point->bonus_point) }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="8">ポイント履歴は存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
