@extends('layouts.master')

@section('title', '成果取り込み')

@section('head.load')
{{ Tag::script('/CryptoJS_v3.1.2/components/core-min.js', ['type' => 'text/javascript']) }}
{{ Tag::script('/CryptoJS_v3.1.2/components/sha256-min.js', ['type' => 'text/javascript']) }}

<script type="text/javascript"><!--
$(function() {
    $('#AdFactorySumButton').on('click', function(event) {
        var p = [];

        $('.AdFactoryInput').each(function(index, element) {
            var e = $(element);
            p.push({name: e.attr('name'), value: e.val()});
        });

        p.sort(function(a,b){
            if(a.name < b.name){return -1;}
            if(a.name > b.name){return 1;}
            return 0;
        });

        var t = '';
        for(var k in p) { // オブジェクトの中のプロパティ名を取り出す。
            t = t + p[k].value;
        }
        t = t + '{{ config('ad_factory.site_key') }}';

        $('#AdFactorySum').val(CryptoJS.SHA256(t));
    });
});

//-->
</script>
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

@php
$admin_map = \App\Admin::whereIn('role', [\App\Admin::ADMIN_ROLE, \App\Admin::SUPPORT_ROLE])
    ->get()
    ->mapWithKeys(function ($item) {
        return [$item->id => $item->id.':'.$item->name];
    })
    ->all();

$asp_map = \App\Asp::where('status', '=', 0)
    ->pluck('name', 'id')
    ->all();

$asp_data_list = [
    ['id' => 1, 'method' => 'get', 'params' => [
        ['key' => 'point_id1', 'label' => 'ポイントID1',],
        ['key' => 'point_id2', 'label' => 'ポイントID2',],
        ['key' => 'as_agr_id', 'label' => '契約ID',],
        ['key' => 'decide_flg', 'label' => '確定フラグ', 'list' => ['' => ':発生', 'Y' => 'Y:承認', 'D' => 'D:非承認'],],
        ['key' => 'order_id', 'label' => '注文ID',],
        ['key' => 'sales_ymd', 'label' => '売上日時:YYYYMMDDHHIISS',],
        ['key' => 'sales_money', 'label' => '報酬対象売上額',],
        ['key' => 'pay_money', 'label' => '成果報酬額',],
    ],],
    ['id' => 2, 'method' => 'get', 'params' => [
        ['key' => 'user_id', 'label' => 'ユーザID',],
        ['key' => 'thanks_id', 'label' => 'サンクスID',],
        ['key' => 'attestation_flag', 'label' => 'アクションの認否', 'list' => ['' => ':アクションが起きた時', '0' => '0:認証', '1' => '1:非認証'],],
        ['key' => 'action_id', 'label' => 'アクションID',],
        ['key' => 'action_time', 'label' => '成果発生時間:YYYYMMDDHHIISS',],
        ['key' => 'order_amount', 'label' => '注文金額',],
        ['key' => 'commission', 'label' => '報酬',],
    ],],
    ['id' => 7, 'method' => 'get', 'params' => [
        ['key' => 'add', 'label' => '貴社サイト会員ID',],
        ['key' => 'm', 'label' => '広告主ID',],
        ['key' => 'status', 'label' => '成果の状態', 'list' => ['0' => '0:未承認', '1' => '1:承認', '2' => '2:却下'],],
        ['key' => 'uniq', 'label' => 'ユニーク文字列',],
        ['key' => 'hdate', 'label' => '発生日:YYYYMMDDHHIISS',],
        ['key' => 'totalprice', 'label' => '合計金額',],
        ['key' => 'reward', 'label' => '報酬',],
    ],],
    ['id' => 10, 'method' => 'get', 'params' => [
        ['key' => 'u1', 'label' => 'ユーザー識別コード1',],
        ['key' => 'u2', 'label' => 'ユーザー識別コード2',],
        ['key' => 'advid', 'label' => '広告ID',],
        ['key' => 'affid', 'label' => 'アフィリエイトID',],
        ['key' => 'appstat', 'label' => '承認状態', 'list' => ['1' => '1:未承認', '2' => '2:承認', '3' => '3:否認',],],
        ['key' => 'ocrdt', 'label' => '発生日時:YYYYMMDDHHIISS',],
        ['key' => 'result', 'label' => '報酬予定金額',],
        ['key' => 'amount', 'label' => '注文金額',],
        ['key' => 'price', 'label' => '報酬金額s',],
    ],],
    ['id' => 13, 'method' => 'get', 'params' => [
        ['key' => 'user', 'label' => 'ユーザコード',],
        ['key' => 't_id', 'label' => '成果地点ごとに付与しているID',],
        ['key' => 'act_id', 'label' => 'アクションごとのID',],
        ['key' => 'attest', 'label' => '認証状態', 'list' => ['' => ':未認証', '1' => '1:認証', '0' => '0:否認',],],
        ['key' => 'act_date', 'label' => 'アクションの発生した日時:YYYYMMDDHHIISS',],
        ['key' => 'sales', 'label' => '購入金額',],
        ['key' => 'price', 'label' => '報酬金額',],
        ['key' => 'sum', 'label' => '報酬額',],
    ],],
    ['id' => 15, 'method' => 'get', 'params' => [
        ['key' => 'RA', 'label' => '識別値1',],
        ['key' => 'awaffId', 'label' => 'メディアID_広告ID',],
        ['key' => 'acflag', 'label' => '通知種類', 'list' => ['0' => '0:アクション通知', '1' => '1:承認通知', '2' => '2:否認通知',],],
        ['key' => 'actime', 'label' => 'アクション時間:YYYY/MM/DD HH:II:SS',],
        ['key' => 'awprice', 'label' => '成果単価',],
    ],],
    ['id' => 16, 'method' => 'post', 'params' => [
        ['key' => 'pid', 'label' => 'ポイントバック識別用ID',],
        ['key' => 'id', 'label' => '広告主ID_広告ID_AFRo会員ID_メディアID',],
        ['key' => 'session', 'label' => '成果を特定するID',],
        ['key' => 'status', 'label' => '成果の状態コード', 'list' => ['0' => '0:未承認', '1' => '1:承認', '2' => '2:否認',],],
        ['key' => 'action', 'label' => '成果の発生した日時:YYYYMMDDHHIISS',],
        ['key' => 'price', 'label' => '単価',],
    ],],
    ['id' => 17, 'method' => 'get', 'params' => [
        ['key' => 'dis', 'label' => 'ユーザー識別情報',],
        ['key' => 'ad', 'label' => '広告ID',],
        ['key' => 'app', 'label' => '承認ステータス', 'list' => ['1' => '1:承認待ち', '2' => '2:承認', '3' => '3:非承認',],],
        ['key' => 'amt', 'label' => '合計金額',],
    ],],
    ['id' => 18, 'method' => 'get', 'params' => [
        ['key' => 'sk', 'label' => 'サイトキー',],
        ['key' => 'op1', 'label' => 'オプション1',],
        ['key' => 'cpn_id', 'label' => 'キャンペーンID',],
        ['key' => 'cv_id', 'label' => '成果ID',],
        ['key' => 'cv_status', 'label' => '成果ステータス', 'list' => ['1' => '1:発生', '2' => '2:承認', '3' => '3:非承認',],],
        ['key' => 'occurred_date', 'label' => '成果発生日時:YYYYMMDDHHIISS',],
        ['key' => 'price', 'label' => '流通金額',],
        ['key' => 'amount', 'label' => '成果金額',],
    ],],
    ['id' => 25, 'method' => 'get', 'params' => [
        ['key' => 'user', 'label' => 'ユーザー固有ID',],
        ['key' => 'rid', 'label' => '任意のパラメータ',],
        ['key' => 'adid', 'label' => '広告ID',],
        ['key' => 'orders_id', 'label' => '成果ID',],
        ['key' => 'approved', 'label' => '承認ステータス', 'list' => ['0' => '0:未承認', '1' => '1:承認', '2' => '2:拒否',],],
        ['key' => 'time_jst', 'label' => '発生通知時刻:YYYYMMDDHHIISS',],
        ['key' => 'amount', 'label' => '購入金額',],
        ['key' => 'user_pay', 'label' => 'ユーザー報酬',],
    ],],
    ['id' => 26, 'method' => 'get', 'params' => [
        ['key' => 'identifier', 'label' => 'ユーザーID',],
        ['key' => 'campaign_id', 'label' => 'プロモーションID',],
        ['key' => 'achieve_id', 'label' => '成果ID',],
        ['key' => 'accepted_time', 'label' => 'アクション日時:YYYY-MM-DDTHH:II:SS',],
        ['key' => 'payment', 'label' => '媒体報酬',],
    ],],
    ['id' => 27, 'method' => 'get', 'params' => [
        ['key' => 'cv_id', 'label' => '注文ID',],
        ['key' => 'suid', 'label' => 'ユーザーID',],
        ['key' => 'xad', 'label' => 'アフィリエイトID',],
        ['key' => 'status', 'label' => '状態', 'list' => ['0' => '発生', '1' => '承認', '2' => '否認' ]],
        ['key' => 'retail', 'label' => '広告主成果報酬額',],
        ['key' => 'price', 'label' => '成果報酬額',],
        ['key' => 'cvdate_dt', 'label' => '発生日時:YYYY-MM-DDT_HH:II:SS',],
    ],],
    ['id' => 28, 'method' => 'get', 'params' => [
        ['key' => 'achieve_id', 'label' => '注文ID',],
        ['key' => 'identifier', 'label' => 'ユーザーID',],
        ['key' => 'campaign_id', 'label' => 'キャンペーンID',],
        ['key' => 'advertisement_id', 'label' => 'アドバタイズメントID',],
        ['key' => 'status', 'label' => '状態', 'list' => ['0' => '発生', '1' => '承認', '2' => '否認' ]],
        ['key' => 'sales_amount', 'label' => 'ユーザー⽀払⾦額',],
        ['key' => 'point', 'label' => 'ポイント',],
        ['key' => 'cvdate_dt', 'label' => '発生日時:YYYYMMDDHHIISS',],
    ],],
    ['id' => 29, 'method' => 'get', 'params' => [
        ['key' => 'cv_id', 'label' => '注文ID',],
        ['key' => 'suid', 'label' => 'ユーザーID',],
        ['key' => 'xad', 'label' => 'アフィリエイトID',],
        ['key' => 'status', 'label' => '状態', 'list' => ['0' => '発生', '1' => '承認', '2' => '否認' ]],
        ['key' => 'retail', 'label' => '広告主成果報酬額',],
        ['key' => 'price', 'label' => '成果報酬額',],
        ['key' => 'r_date', 'label' => '発生日時:YYYY-MM-DDT_HH:II:SS',],
    ],],
    ['id' => 30, 'method' => 'get', 'params' => [
        ['key' => 'cv_id', 'label' => '注文ID',],
        ['key' => 'suid', 'label' => 'ユーザーID',],
        ['key' => 'xad', 'label' => 'アフィリエイトID',],
        ['key' => 'status', 'label' => '状態', 'list' => ['0' => '発生', '1' => '承認', '2' => '否認' ]],
        ['key' => 'price', 'label' => '成果報酬額',],
        ['key' => 'approve_date', 'label' => '発生日時:YYYYMMDDHHMMSS',],
    ],],
    ['id' => 19, 'method' => 'get', 'params' => [
        ['key' => 'user_id', 'label' => 'ユーザーID',],
        ['key' => 'content_id', 'label' => 'コンテンツID', 'list' => ['1' => '1:アンケート', '2' => '2:クイズ', '4' => '4:タイピング',
            '5' => '5:リバーシ', '6' => '6:暗算', '7' => '7:カレンダー', '8' => '8:スタンプ',],],
        ['key' => 'play_id', 'label' => 'コンテンツ内ユニークID',],
        ['key' => 'date', 'label' => '発生日時:YYYY-MM-DD HH:II:SS',],
    ],],
    ['id' => 23, 'method' => 'post', 'params' => [
        ['key' => 'uid', 'label' => 'ユーザーID',],
        ['key' => 'game_class', 'label' => 'アンケート種別', 'list' => ['1' => '1:コラムとアンケート', '6' => '6:写真とアンケート',
            '26' => '26:観察力とアンケート', '18' => '18:動物図鑑とアンケート', '34' => '34:日本百景とアンケート', '36' => '36:料理とアンケート',
            '16' => '16:漫画とアンケート', '54' => '54:ひらめきとアンケート', '58' => '58:アンケートMix',],],
        ['key' => 'game_action_id', 'label' => 'ゲーム実行ID',],
        ['key' => 'title', 'label' => 'アンケートタイトル',],
        ['key' => 'answerDate', 'label' => '回答日時:YYYY-MM-DD HH:II:SS',],
        ['key' => 'user_get_point', 'label' => 'ポイント数',],
    ],],
];

$url_format = config('app.kick_url').'/aff_rewards/%d/create';
@endphp

@foreach ($asp_data_list as $asp_data)
<h2>{{ $asp_map[$asp_data['id']] }}</h2>
{{ Tag::formOpen(['url' => sprintf($url_format, $asp_data['id']), 'method' => $asp_data['method']]) }}
@csrf    
<fieldset>
        @foreach ($asp_data['params'] as $param)
        @php
        $input_class = 'form-control';
        if ($asp_data['id'] == 18) {
            $input_class = $input_class.' AdFactoryInput';
        }
        $input_id = 'Test'.$asp_data['id'].\Illuminate\Support\Str::studly($param['key']);
        @endphp
        <div class="form-group">
            <label for="{{ $input_id }}">{{ $param['key'] }}[{{ $param['label'] }}]</label><br />
            @if (isset($param['list']))
            {{ Tag::formSelect($param['key'], $param['list'], '', ['class' => $input_class, 'id' => $input_id]) }}
            @else
            {{ Tag::formText($param['key'], '', ['class' => $input_class, 'id' => $input_id]) }}
            @endif
            <br />
        </div>
        @endforeach
        @if ($asp_data['id'] == 18)
        <div class="form-group">
            <label>sum</label><br />
            {{ Tag::formText('sum', '', ['class' => 'form-control', 'id' => 'AdFactorySum']) }}
            <input class="form-control" type="button" value="暗号化" id="AdFactorySumButton" /><br />
        </div>
        @endif
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
<br />

@endforeach

<h2>Fancrew</h2>
{{ Tag::formOpen(['url' => config('app.kick_url').'/fancrew/eventMessage.receive.php', 'method' => 'post']) }}
@csrf    
<fieldset>
        <div class="form-group">
            <textarea name="xml" cols="120" rows="30"></textarea>
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
<br />

<h2>プログラム成果CSVインポート</h2>
{{ Tag::formOpen(['url' => config('app.kick_url').'/csv/import_program', 'method' => 'post', 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="ProgramAdminId">管理者ID</label><br />
            {{ Tag::formSelect('admin_id', $admin_map, null, ['class' => 'form-control', 'id' => 'ProgramAdminId']) }}<br />
        </div>
        <div class="form-group">
            <label for="ProgramStatus">状態</label><br />
            {{ Tag::formSelect('status', ['2' => '配布待ち', 1 => '却下', 4 => '発生'], null, ['class' => 'form-control', 'id' => 'ProgramStatus']) }}<br />
        </div>
        <div class="form-group">
            <label for="ProgramCSVFile">CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'ProgramCSVFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
<br />

<h2>プログラムが存在しない成果CSVインポート</h2>
{{ Tag::formOpen(['url' => config('app.kick_url').'/csv/import_programless', 'method' => 'post', 'files' => true]) }}
@csrf    
<fieldset>
        <div class="form-group">
            <label for="ProgramLessAdminId">管理者ID</label><br />
            {{ Tag::formSelect('admin_id', $admin_map, null, ['class' => 'form-control', 'id' => 'ProgramLessAdminId']) }}<br />
        </div>
        <div class="form-group">
            <label for="ProgramLessCSVFile">CSVファイル</label><br />
            {{ Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput', 'id' => 'ProgramLessCSVFile']) }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('実行', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
<br />

@endsection
