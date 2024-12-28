@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('users.edit', ['user' => $user]), 'ユーザー更新') }}</li>
<li>{{ Tag::link(route('users.point_history', ['user' => $user]), 'ポイント履歴') }}</li>
<li>{{ Tag::link(route('users.login_history', ['user' => $user]), 'ログイン履歴') }}</li>
<li class="active">{{ Tag::link(route('banks.account_list', ['user' => $user]), '銀行口座一覧') }}</li>
<li>
    <a href="javascript:void(0);" onclick="var ok=confirm('銀行口座をリセットしますか？');if (ok) $('#DeleteBankAccount').submit(); return false;">銀行口座リセット</a>
    {{ Tag::formOpen(['url' => route('banks.delete_account', ['user' => $user]), 'id' => 'DeleteBankAccount']) }}
    @csrf
    @method('DELETE')
    {{ Tag::formClose() }}
</li>
@endsection

@section('content')
<h2>銀行口座一覧</h2>

<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>銀行</th>
        <th>銀行コード</th>
        <th>支店</th>
        <th>支店コード</th>
        <th>氏名</th>
        <th rowspan="2">登録日時</th>
    </tr>
    <tr>
        <th colspan="4">口座番号</th>
        <th>氏名カナ</th>
    </tr>
    @php
    $now = Carbon\Carbon::now();
    $status_map = [0 => 'active', 1 => 'danger'];
    @endphp
    @forelse ($bank_account_list as $bank_account)
    <tr class="{{ $status_map[$bank_account->status] }}">
        <td>{{ $bank_account->bank->name ?? '不明' }}</td>
        <td>{{ $bank_account->bank_code }}</td>
        <td>{{ $bank_account->bank_branch->name ?? '不明' }}</td>
        <td>{{ $bank_account->branch_code }}</td>
        <td>{{ $bank_account->last_name }}&nbsp;{{ $bank_account->first_name }}</td>
        <td rowspan="2">{{ $bank_account->created_at->format('Y-m-d H:i:s') }}</td>
    </tr>
    <tr class="{{ $status_map[$bank_account->status] }}">
        <td colspan="4">{{ $bank_account->number }}</td>
        <td>{{ $bank_account->last_name_kana }}&nbsp;{{ $bank_account->first_name_kana }}</td>
    </tr>
    @empty
    <tr><td colspan="6">銀行口座は存在しません</td></tr>
    @endforelse
</table>

@endsection