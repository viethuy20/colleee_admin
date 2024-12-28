@extends('layouts.master')

@section('title', 'ユーザーアカウント管理')

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li class="active">{{ Tag::link(route('users.csv'), 'ユーザーアカウント管理') }}</li>
<li>{{ Tag::link(route('users.kpi'), 'KPI') }}</li>
@endsection

@section('content')
@if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
<h2>GMOポイ活非アクティブユーザー削除</h2>
{{ Tag::formOpen(['url' => route('users.delete_nonaction_users'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="UserCSVFile">Cuenote非アクティブユーザーメーリングリストCSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'UserCSVFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
<br />
@endif

<h2>Fancrew退会者ユーザーID</h2>
{{ Tag::formOpen(['url' => route('aff_accounts.export_removed_fancrew_list')]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label>CSVファイル書式</label><br />
            <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
                <tr>
                    <th>項目</th>
                    <th>FancrewユーザーID</th>
                    <th>GMOポイ活ユーザー名</th>
                </tr>
                <tr>
                    <th>書式</th>
                    <td>整数</td>
                    <td>文字列<br />※{{ App\User::getNameById(0) }}</td>
                </tr>
            </table>
        </div>
        <div class="form-group">{{ Tag::formSubmit('エクスポート', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
<br />
<h2>Fancrew退会者ユーザーID削除</h2>
{{ Tag::formOpen(['url' => route('aff_accounts.removed_fancrew'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="AffAccountCSVFile">CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'AffAccountCSVFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endif

@endsection
