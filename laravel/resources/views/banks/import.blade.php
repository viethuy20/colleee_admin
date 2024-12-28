@extends('layouts.master')

@section('title', '金融機関管理')

@section('menu')
@include('partials.menu.point_exchange_menu', ['currentRoute' => ''])
<li class="active">{!! Tag::link(route('banks.import'), '銀行・支店インポート') !!}</li>
@endsection

@section('menu.extra')
@endsection

@section('content')
<h2>銀行</h2>

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{!! Tag::formOpen(['url' => route('banks.import'), 'files' => true]) !!}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="BankFile">銀行CSVファイル</label><br />
            {!! Tag::formFile('file[]', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'BankFile', 'multiple' => 'multiple']) !!}
        </div>
        <div class="form-group">
            <label for="BankFileEncode">ファイルエンコード</label><br />
            {!! Tag::formSelect('encode', ['shift-jis' => 'Shift-JIS', 'utf-8' => 'UTF-8', 'euc-jp' => 'EUC-JP'], 'Shift-JIS', ['class' => 'form-control', 'id' => 'BankFileEncode']) !!}
        </div>
        <div class="form-group">{!! Tag::formSubmit('送信', ['class' => 'btn btn-default']) !!}</div>
    </fieldset>
{!! Tag::formClose() !!}

@endsection