@extends('layouts.master')

@section('title', 'ユーザーポイント管理')

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>ユーザープログラムポイント補填</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>名称</th>
        <th>GMOポイ活ユーザーID</th>
        <th>補填プログラムID</th>
        <th>通常配布ポイント数</th>
        <th>ボーナス配布ポイント数</th>
        <th>件名</th>
    </tr>
    <tr>
        <th>書式</th>
        <td>半角英数字</td>
        <td>数字</td>
        <td>数値</td>
        <td>数値</td>
        <td>文字列</td>
    </tr>
    <tr>
        <th>説明</th>
        <td>例:{{ App\User::getNameById(0) }}<br />GMOポイ活ユーザーID</td>
        <td>
            必ずマイナスの値になります<br />
            GMOポイ活ユーザーIDと補填プログラムIDが完全に一致した場合、<br />
            重複データと判断するので、新規の補填を行う場合は<br />
            必ず新規の補填プログラムIDを使用してください。<br />
            新規補填プログラムID:{{ $last_program_id }}</td>
        <td>
            通常配布ポイント + ボーナス配布ポイントがユーザーに付与されます。<br />
            通常配布ポイントはアフィリエイトの成果として付与されます。
        </td>
        <td>
            通常配布ポイント + ボーナス配布ポイントがユーザーに付与されます。<br />
            ボーナス配布ポイントは内部インセンティブとして付与されます。
        </td>
        <td>ユーザーのポイント履歴に表示されます</td>
    </tr>
</table>
{{ Tag::formOpen(['url' => route('user_points.import_program'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="ProgramFile">ユーザープログラム補填CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'ProgramFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}


<h2>ユーザー特別プログラムポイント補填</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>名称</th>
        <th>GMOポイ活ユーザーID</th>
        <th>補填特別プログラムID</th>
        <th>通常配布ポイント数</th>
        <th>ボーナス配布ポイント数</th>
        <th>件名</th>
    </tr>
    <tr>
        <th>書式</th>
        <td>半角英数字</td>
        <td>数字</td>
        <td>数値</td>
        <td>数値</td>
        <td>文字列</td>
    </tr>
    <tr>
        <th>説明</th>
        <td>例:{{ App\User::getNameById(0) }}<br />GMOポイ活ユーザーID</td>
        <td>
            必ずマイナスの値になります<br />
            GMOポイ活ユーザーIDと補填特別プログラムIDが完全に一致した場合、<br />
            重複データと判断するので、新規の補填を行う場合は<br />
            必ず新規の補填特別プログラムIDを使用してください。<br />
            新規補填特別プログラムID:{{ $last_sp_program_id }}</td>
        <td>
            通常配布ポイント + ボーナス配布ポイントがユーザーに付与されます。<br />
            通常配布ポイントはアフィリエイトの成果として付与されます。
        </td>
        <td>
            通常配布ポイント + ボーナス配布ポイントがユーザーに付与されます。<br />
            ボーナス配布ポイントは内部インセンティブとして付与されます。
        </td>
        <td>ユーザーのポイント履歴に表示されます</td>
    </tr>
</table>
{{ Tag::formOpen(['url' => route('user_points.import_sp_program'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="SpProgramFile">ユーザー特別プログラム補填CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'SpProgramFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection