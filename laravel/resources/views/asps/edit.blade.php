@extends('layouts.master')

@section('title', 'ASP管理')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php 
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $sp_program_type)
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
@endforeach
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif


{{ Tag::formOpen(['url' => route('asps.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        <legend>ASP更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $asp['id'] }}
            {{ Tag::formHidden('id', old('id', $asp['id'])) }}
        </div>
        <div class="form-group">
            <label for="AspName">名称</label>
            {{ Tag::formText('name', old('name', $asp['name'] ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'AspName']) }}<br />
        </div>
        <div class="form-group">
            <label for="AspCompany">企業</label>
            {{ Tag::formText('company', old('company', $asp['company'] ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'AspCompany']) }}<br />
        </div>
        <div class="form-group">
            <label for="AspUrl">URL</label><br />
            {{ Tag::formText('url', old('url', $asp['url'] ?? ''), ['class' => 'form-control', 'id' => 'AspUrl']) }}<br />
        </div>
        <div class="form-group">
            <label for="AspNameUrl">商品リンクパラメーター名</label><br />
            {{ Tag::formText('url_parameter_name', old('url_parameter_name', $asp['url_parameter_name'] ?? ''), ['class' => 'form-control', 'id' => 'AspNameUrl']) }}<br />
        </div>
        <div class="form-group">
            <label for="AspAllowIps">許可IPアドレス</label>
            {{ Tag::formTextarea('allow_ips', old('allow_ips', $asp['allow_ips'] ?? ''), ['class' => 'form-control', 'rows' => 6, 'id' => 'AspAllowIps']) }}<br />
            {{ $errors->has('allow_ips') ? $errors->first('allow_ips') : '' }}
        </div>
        <hr />
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
