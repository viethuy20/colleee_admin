@extends('layouts.master')

@section('title', '交換申し込み一括更新')

@section('menu')
@include('partials.menu.point_exchange_menu', ['currentRoute' => 'exchange_requests.import'])
@endsection

@section('content')
<h2>CSVインポート</h2>

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('exchange_requests.import'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="Status">状態</label><br />
            {{ Tag::formSelect('status', [1 => '組み戻し', 0 => '承認'], null, ['class' => 'form-control', 'id' => 'Status']) }}<br />
        </div>
        <div class="form-group">
            <label for="CSVFile">CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'CSVFile']) }}
        </div>
        <div class="form-group">
            <label>CSVファイル書式</label><br />
            <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
                <tr><th>項目</th><th>申し込み番号</th></tr>
                <tr><th>書式</th><td>文字列</td></tr>
                <tr><th>必須</th><td>○</td></tr>
            </table>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection