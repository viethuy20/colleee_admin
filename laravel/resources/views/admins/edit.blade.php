@extends('layouts.master')

@section('title', '管理者管理')

@section('menu')
<li>{{ Tag::link(route('admins.index'), '管理者一覧') }}</li>
<li class="active">{{ Tag::link(route('admins.create'), '管理者登録') }}</li>
@endsection

@section('content')
{{ Tag::formOpen(['url' => route('admins.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($admin->id))
        <legend>管理者更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $admin->id }}
            {{ Tag::formHidden('id', $admin->id) }}
        </div>
        @else
        <legend>管理者作成</legend>
        @endif
        
	    <div class="form-group">
            <label for="AdminName">名称</label>
            {{ Tag::formText('name', $admin->name ?? '', ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'AdminName']) }}<br />
            {{ $errors->has('name') ? $errors->first('name') : '' }}
        </div>
        <div class="form-group">
            <label for="AdminEmail">メールアドレス</label>
            {{ Tag::formText('email', $admin->email ?? '', ['class' => 'form-control', 'maxlength' => '256', 'id' => 'AdminEmail']) }}<br />
            {{ $errors->has('email') ? $errors->first('email') : '' }}
        </div>
        <div class="form-group">
            <label for="AdminRole">権限</label>
            {{ Tag::formSelect('role', config('map.admin_role'), $admin->role, ['class' => 'form-control', 'id' => 'AdminRole']) }}<br />
            {{ $errors->has('role') ? $errors->first('role') : '' }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
