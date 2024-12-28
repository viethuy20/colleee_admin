@extends('layouts.plane')

@section('title', 'ログイン')

@section('layout.content')
<div class="row-fluid">
    <div class="col-md-12">
        <!-- apply custom style -->
        <div class="page-header" style="margin-top:-30px;padding-bottom:0px;">
            <h1><small>
                @if (Session::has('message'))
                {{ Session::get('message') }}
                @endif
            </small></h1>
        </div>
        {!! Tag::formOpen(['route' => 'login', 'method' => 'post']) !!}
        @csrf    
        <fieldset>
                <legend>メールアドレスとパスワードを入れてください</legend>
                <div class="form-group">
                    <label for="UserEmail">メールアドレス</label>
                    {!! Tag::formText('email', '', ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'UserEmail']) !!}<br />
                    {{ $errors->has('email') ? $errors->first('email') : '' }}
                </div>
                <div class="form-group">
                    <label for="UserPassword">パスワード</label>
                    {!! Tag::formPassword('password', ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'UserPassword']) !!}<br />
                    {{ $errors->has('password') ? $errors->first('password') : '' }}
                </div>
                <div class="form-group">{!! Tag::formSubmit('ログイン', ['class' => 'btn btn-default']) !!}</div>
            </fieldset>
        {!! Tag::formClose() !!}
    </div>
</div><!--/row-->
@endsection