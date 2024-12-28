@extends('layouts.master')

@section('title', '管理者管理')

@section('menu')
<li class="active">{{ Tag::link(route('admins.index'), '管理者一覧') }}</li>
<li>{{ Tag::link(route('admins.create'), '管理者登録') }}</li>
@endsection

@section('content')
<h2>管理者</h2>
<table cellpadding="0" cellspacing="0" class="table">
    <tr>
        <th class="actions">操作</th>
        <th>ID</th>
        <th>名称</th>
        <th>状態</th>
        <th>メールアドレス</th>
        <th>権限</th>
        <th>作成日時</th>
        <th>更新日時</th>
        <th>削除日時</th>
    </tr>
    @php 
    $status_map = [0 => ['class' => 'active', 'status' => '利用可'],
        1 => ['class' => 'danger', 'status' => '削除']];
    $role_map = config('map.admin_role');
    @endphp
    @forelse ($admin_list as $admin)
    @php
    $state_id = $admin->status == 1 ? 1 : 0;
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions">
            {{ Tag::link(route('admins.edit', ['admin' => $admin]), '編集', ['class' => 'btn btn-small btn-success']) }}
            {{ Tag::formOpen(['url' => route('admins.reset'),'method' => 'post', 'onclick' => "return confirm('本当にPWを再発行しますか？');"]) }}
            @csrf
            {{ Tag::formHidden('admin_id', $admin->id) }}
                {{ Tag::formSubmit('PW再発行', ['class' => 'btn btn-small btn-success']) }} 
            {{ Tag::formClose() }}
            {{ Tag::formOpen(['url' => route('admins.destroy', ['admin' => $admin])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('削除', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('Delete ".$admin->name."?');"]) }}
            {{ Tag::formClose() }}
        </td>
        <td>{{ $admin->id }}&nbsp;</td>
        <td>{{ $admin->name }}&nbsp;</td>
        <td>{{ $status_map[$state_id]['status'] }}&nbsp;</td>
        <td>{{ $admin->email }}&nbsp;</td>
        <td>{{ $role_map[$admin->role] }}&nbsp;</td>
        <td>{{ $admin->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $admin->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ isset($admin->deleted_at) ? $admin->deleted_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="8">管理者は存在しません</td></tr>
    @endforelse
</table>
@endsection