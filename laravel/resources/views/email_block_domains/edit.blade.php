@extends('layouts.master')

@section('title', 'メールアドレスブロックドメイン管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li{!! (isset($email_block_domain['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('email_block_domains.create'), '新規作成') }}</li>
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('email_block_domains.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($email_block_domain['id']))
        <legend>メールアドレスブロックドメイン更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $email_block_domain['id'] }}
            {{ Tag::formHidden('id', old('id', $email_block_domain['id'] ?? '')) }}
        </div>
        @else
        <legend>メールアドレスブロックドメイン作成</legend>
        @endif
        <div class="form-group">
            <label for="EmailBlockDomainDomain">ドメイン</label>
            {{ Tag::formText('domain', old('domain', $email_block_domain['domain'] ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'EmailBlockDomainDomain']) }}<br />
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
