@extends('layouts.master')

@section('title', 'メールアドレスブロックドメイン管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li class="active">{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.create'), '新規作成') }}</li>
@endsection

@section('content')
<h2>メールアドレスブロックドメイン</h2>

<table cellpadding="0" cellspacing="0" class="table">
    <tr>
        <th class="actions">操作</th>
        <th>ID</th>
        <th>ドメイン</th>
        <th>ブロック状態</th>
    </tr>

    @php
    $status_map = [0 => '有効', 1 => '無効'];
    $class_map = [0 => 'active', 1 => 'danger'];
    @endphp
    @forelse ($email_block_domain_list as $email_block_domain)
    <tr class="{{ $class_map[$email_block_domain->status] }}">
        <td class="actions">
            {{ Tag::link(route('email_block_domains.edit', ['email_block_domain' => $email_block_domain]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            @if ($email_block_domain->status != 0)
            {{ Tag::formOpen(['url' => route('email_block_domains.enable', ['email_block_domain' => $email_block_domain])]) }}
            @csrf    
            {{ Tag::formSubmit('有効化', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('このメールアドレスドメインのブロックを有効化しますか?:".$email_block_domain->domain."');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($email_block_domain->status != 1)
            {{ Tag::formOpen(['url' => route('email_block_domains.destroy', ['email_block_domain' => $email_block_domain])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('無効化', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('このメールアドレスドメインのブロックを無効化しますか?:".$email_block_domain->domain."');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td>{{ $email_block_domain->id }}&nbsp;</td>
        <td>{{ $email_block_domain->domain }}&nbsp;</td>
        <td>{{ $status_map[$email_block_domain->status] }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="4">メールアドレスブロックドメインは存在しません</td></tr>
    @endforelse
</table>
@endsection
