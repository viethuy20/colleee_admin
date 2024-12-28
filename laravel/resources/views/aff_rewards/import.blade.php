@extends('layouts.master')

@section('title', '成果取り込み')

@section('menu')
<li>{{ Tag::link(route('external_links.index'), 'クリック一覧') }}</li>
<li>{{ Tag::link(route('aff_rewards.index'), '成果一覧') }}</li>
<li class="active">{{ Tag::link(route('aff_rewards.import'), '成果インポート') }}</li>
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>プログラム成果CSVインポート</h2>
{{ Tag::formOpen(['url' => route('aff_rewards.import_program_csv'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="Status">状態</label><br />
            {{ Tag::formSelect('status', ['2' => '配布待ち', 1 => '却下', 4 => '発生'], null, ['class' => 'form-control', 'id' => 'Status']) }}<br />
        </div>
        <div class="form-group">
            <label>CSVファイル書式</label><br />
            <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
                <tr>
                    <th>項目</th>
                    <th>GMOポイ活ユーザー名</th>
                    <th>ASP</th>
                    <th>データ連携ID</th>
                    <th>注文番号</th>
                    <th>成果発生日時</th>
                    <th>商品購入金額</th>
                    <th>追加タイトル</th>
                </tr>
                <tr>
                    <th>書式</th>
                    <td>文字列<br />※{{ App\User::getNameById(0) }}</td>
                    <td>
                        整数<br />
                        ※1:A8.net, 2:Janet,3:ValueCommerce,<br />
                        4:楽天アフィリエイト,5:TGアフィリエイト,<br />
                        6:LinkShare,7:アクセストレード,10:アフィタウン,<br />
                        13:Smart-c,15:アフィリエイトウォーカー,16:AFRo,<br />
                        17:アルテマアフィリエイト,18:アドファクトリー,<br />
                        25:SmaAD,26:AppDriver,27:TRUEアフィリエイト,<br />
                        28:GREE Ads Reward, 29:AD TRACK,<br />
                        30:SKYFLAG,47:Circuit X<br />
                    </td>
                    <td>文字列</td>
                    <td>文字列</td>
                    <td>文字列<br />※YYYY-MM-DD HH:II:SS</td>
                    <td>整数</td>
                    <td>文字列</td>
                </tr>
                <tr>
                    <th>必須</th>
                    <td>○</td>
                    <td>○</td>
                    <td>○</td>
                    <td>○</td>
                    <td>○</td>
                    <td>○<br />※定額案件の場合は0が指定可能</td>
                    <td>×</td>
                </tr>
            </table>
        </div>
        <div class="form-group">
            <label for="CSVFile">CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'CSVFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

<br />

<h2>プログラムが存在しない成果CSVインポート</h2>
{{ Tag::formOpen(['url' => route('aff_rewards.import_programless_csv'), 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label>CSVファイル書式</label><br />
            <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
                <tr>
                    <th>項目</th>
                    <th>GMOポイ活ユーザー名</th>
                    <th>ASP</th>
                    <th>注文番号</th>
                    <th>成果発生日時</th>
                    <th>データ連携ID</th>
                    <th>タイトル</th>
                    <th>ポイント数</th>
                    <th>金額</th>
                </tr>
                <tr>
                    <th>書式</th>
                    <td>文字列<br />※{{ App\User::getNameById(0) }}</td>
                    <td>
                        整数<br />
                        ※11:Fancrew,19:セレス,20:Sansan,23:Estlier,31:まいにちクイズボックス,32:かんたんゲームボックス,39:運だめし　スロットボックス
                    </td>
                    <td>文字列</td>
                    <td>文字列<br />※YYYY-MM-DD HH:II:SS</td>
                    <td>文字列</td>
                    <td>文字列</td>
                    <td>整数</td>
                    <td>整数</td>
                </tr>
                <tr>
                    <th>必須</th>
                    <td>○</td>
                    <td>○</td>
                    <td>○</td>
                    <td>○</td>
                    <td>△<br />※Fancrew,Estlierの場合は必須</td>
                    <td>△<br />※Fancrew,Estlierの場合は必須</td>
                    <td>△<br />※Fancrew,Sansanの場合は必須</td>
                    <td>△<br />※Fancrewの場合は必須</td>
                </tr>
            </table>
        </div>
        <div class="form-group">
            <label for="CSVFile">CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'CSVFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
