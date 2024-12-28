@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('head.load')
<script type="text/javascript"><!--
$(function() {
    $('#UserEmailReminderDialog').dialog({
        autoOpen: false,
        closeOnEscape: true,
        modal: true,
        minWidth: 480,
        minHeight: 240,
        //height: 'auto'
    });
    $('#UserEmailReminderButton').on('click', function() {
        var dialog = $('#UserEmailReminderDialog');
        dialog.show();
        dialog.dialog('open');
    });
});
//-->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
<li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
<li class="active">{{ Tag::link(route('users.edit', ['user' => $user['id']]), 'ユーザー更新') }}</li>
<li>{{ Tag::link(route('users.point_history', ['user' => $user['id']]), 'ポイント履歴') }}</li>
<li>{{ Tag::link(route('users.login_history', ['user' => $user['id']]), 'ログイン履歴') }}</li>
<li>{{ Tag::link(route('users.edit_history', ['user' => $user['id']]), '更新履歴') }}</li>
<li>{{ Tag::link(route('pre_aff_rewards.list', ['user' => $user['id']]), 'ポイント先出し成果一覧') }}</li>
@if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
<li>{{ Tag::link(route('banks.account_list', ['user' => $user['id']]), '銀行口座一覧') }}</li>
@endif
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<div id="UserEmailReminderDialog" title="メールアドレス変更">
    {{ Tag::formOpen(['url' => route('users.email_reminder'), 'onsubmit' => "return confirm('メールアドレス変更を実行しますか?');"]) }}
    @csrf    
    {{ Tag::formHidden('user_id', $user['id']) }}
        <fieldset>
            <div class="form-group">
                <label for="UserEmail">新規メールアドレス</label><br />
                {{ Tag::formText('email', '', ['required' => 'required', 'id' => 'UserEmail']) }}
            </div>
            <div class="form-group">
                <label for="UserEmailConfirmation">確認用新規メールアドレス</label><br />
                {{ Tag::formText('email_confirmation', '', ['required' => 'required', 'id' => 'UserEmailConfirmation']) }}
            </div>
            <div class="form-group">
                {{ Tag::formSubmit('実行', ['class' => 'btn btn-success btn-small']) }}
            </div>
        </fieldset>
    {{ Tag::formClose() }}
</div>

@php
$sex_map = config('map.sex');
$reset_tel = isset($user['tel']) && in_array($user['status'], [\App\User::SELF_WITHDRAWAL_STATUS, \App\User::FORCE_WITHDRAWAL_STATUS, \App\User::OPERATION_WITHDRAWAL_STATUS], true);
@endphp

@if ($reset_tel)
{{ Tag::formOpen(['url' => route('users.reset_tel'), 'method' => 'post', 'onsubmit' => "return confirm('電話番号リセットを実行しますか?');", 'id' => 'UserResetTelForm']) }}
@csrf    
{{ Tag::formHidden('user_id', $user['id']) }}
{{ Tag::formClose() }}
<script type="text/javascript"><!--
$(function() {
    $('#UserResetTelButton').on('click', function() {
        $('#UserResetTelForm').submit();
    });
});
//-->
</script>
@endif

{{ Tag::formOpen(['url' => route('users.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
{{ Tag::formHidden('id', old('id', $user['id'])) }}
    <fieldset>
        <legend>ユーザー更新</legend>
        <div class="form-group"><label>ID</label><br />{{ $user['name'] }}</div>
        <div class="form-group"><label>旧ID</label><br />{{ $user['old_id'] ?? '' }}</div>
        <div class="form-group"><label>FancrewユーザーID</label><br />{{ $user['fancrew_user_id'] ?? '' }}</div>
        <div class="form-group">
            <label>LINEユーザーID</label><br />
            {{ $user['line_id'] ?? '' }}&nbsp;
        </div>
        <div class="form-group">
            <label>GoogleユーザーID</label><br />
            {{ $user['google_id'] ?? '' }}&nbsp;
        </div>
        <div class="form-group"><label>ニックネーム</label><br />{{ $user['nickname'] ?? '' }}</div>
        <div class="form-group">
            <label>メールアドレス</label><br />
            {{ $user['email'] ?? '' }}&nbsp;<a id="UserEmailReminderButton" class="btn btn-default">変更</a>
        </div>
        <div class="form-group"><label>生年月日</label><br />{{ $user['birthday'] ?? '' }}</div>
        <div class="form-group"><label>性別</label><br />{{ $sex_map[$user['sex']] ?? 'その他' }}</div>

        <div class="form-group">
            <label>メールアドレス受信設定</label><br />
            @if ($user['email_status'] == 0)
            可能
            @else
            不可能
            @endif
        </div>
        <div class="form-group"><label>現在のポイント</label><br />{{ number_format($user['point']) }}</div>
        <div class="form-group"><label>交換ポイント累計</label><br />{{ number_format($user['exchanged_point']) }}</div>
        <div class="form-group"><label>会員ランク</label><br />{{ config('map.user_rank')[$user['rank']] }}</div>
        <div class="form-group"><label>プロモーションID</label><br />{{ $user['promotion_id'] }}</div>
        <div class="form-group"><label>フレンドコード</label><br />{{ $user['friend_code']}}</div>
        <div class="form-group"><label>紹介ユーザーID</label><br />{{ $user['friend_user_name'] ?? ''}}</div>

        <div class="form-group">
            <label for="UserTel">電話番号</label><br />
            {{ $user['tel'] }}
            @if ($reset_tel)
            &nbsp;<a id="UserResetTelButton" class="btn btn-default">リセット</a>
            @endif
            <br />
        </div>
        <div class="form-group">
            <label>スマートフォン</label><br />
            {{ $user['carriers'] ?? ''}}
        </div>
        <div class="form-group">
            <label for="UserStatus">会員ステータス</label><br />
            @php
            $user_status = old('status', $user['status']);
            $user_status_map = config('map.user_status');
            $user_status_id_list = [];
            if ($user['status'] == \App\User::COLLEEE_STATUS) {
                $user_status_id_list = [\App\User::COLLEEE_STATUS, \App\User::LOCK1_STATUS,
                    \App\User::LOCK2_STATUS, \App\User::OPERATION_WITHDRAWAL_STATUS,
                    \App\User::FORCE_WITHDRAWAL_STATUS];
            } elseif ($user['status'] == \App\User::LOCK1_STATUS) {
                $user_status_id_list = [\App\User::COLLEEE_STATUS, \App\User::LOCK1_STATUS,
                    \App\User::LOCK2_STATUS, \App\User::FORCE_WITHDRAWAL_STATUS];
            } elseif ($user['status'] == \App\User::LOCK2_STATUS) {
                $user_status_id_list = [\App\User::COLLEEE_STATUS, \App\User::LOCK2_STATUS,
                    \App\User::FORCE_WITHDRAWAL_STATUS];
            }
            $user_status_map = Illuminate\Support\Arr::where($user_status_map, function($value, $key) use($user_status_id_list) {
                return in_array($key, $user_status_id_list, true);
            });
            @endphp
            @if (!empty($user_status_map))
            {{ Tag::formSelect('status', $user_status_map, $user_status, ['class' => 'form-control', 'id' => 'UserStatus']) }}<br />
            @else
            {{ config('map.user_status')[$user_status] }}
            {{ Tag::formHidden('status', $user_status) }}
            @endif
        </div>
        <div class="form-group">
            <label for="UserEmailMagazine">メルマガ受信設定</label><br />
            {{ Tag::formSelect('email_magazine', [0 => '受信しない', 1 => '受信する'], old('email_magazine', $user['email_magazine']), ['class' => 'form-control', 'id' => 'UserEmailMagazine']) }}<br />
        </div>
        <div class="form-group"><label>会員登録日時</label><br />{{ $user['created_at'] }}</div>
        <div class="form-group"><label>更新日時</label><br />{{ $user['updated_at'] }}</div>
        <div class="form-group"><label>退会日時</label><br />{{ $user['deleted_at'] ?? '' }}</div>
        <div class="form-group"><label>更新者</label><br />{{ $user['updated_admin_id'] > 0 ? $user['updated_admin_id'] : '' }}</div>
        <div class="form-group"><label>会員登録時IPアドレス</label><br />{{ $user['ip'] }}</div>
        <div class="form-group"><label>ポイント失効期限</label><br />{{ $user['point_expire_at'] ?? '' }}</div>
        <div class="form-group">
            <label for="UserMemo">内部連絡用メモ</label><br />
            {{ Tag::formTextarea('memo', old('memo', $user['memo'] ?? null), ['class' => 'form-control', 'rows' => 10, 'id' => 'UserMemo']) }}<br />
        </div>

        <div class="form-group">{{ Tag::formSubmit('更新', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
