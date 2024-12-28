@extends('layouts.master')

@section('title', '管理者PW変更')

@section('menu')
<li class="active">{{ Tag::link(route('admins.update_password'), 'パスワード変更') }}</li>
<li>{{ Tag::link('/logout/', 'ログアウト') }}</li>
@endsection

@section('content')
<div class="row-fluid"><div class="col-md-12">
    {{ Tag::formOpen(['url' => route('admins.update_password'), 'method' => 'post']) }}
    @csrf    
    <fieldset>
            <legend>パスワードを入力して下さい</legend>
            <div class="form-group">
                <label for="AdminPassword">パスワード</label>
                {{ Tag::formPassword('password', ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'AdminPassword']) }}<br />
                {{ $errors->has('password') ? $errors->first('password') : '' }}
            </div>
            <div class="form-group">
                <label for="AdminPasswordConfirmation">パスワード再入力</label>
                {{ Tag::formPassword('password_confirmation', ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'AdminPasswordConfirmation']) }}<br />
                {{ $errors->has('password_confirmation') ? $errors->first('password_confirmation') : '' }}
            </div>
            <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
        </fieldset>
    {{ Tag::formClose() }}
</div></div><!--/row-->
@endsection