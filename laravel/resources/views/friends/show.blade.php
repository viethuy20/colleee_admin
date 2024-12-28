@extends('layouts.master')

@section('title', 'お友達紹介管理')
@section('menu')
<li>{{ Tag::link(route('friends.index'), 'スケジュール一覧') }}</li>
<li>{{ Tag::link(route('friends.newdata'), 'スケジュール追加') }}</li>
@endsection

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

@section('content')
<!-- bootstrap-datepickerを読み込む -->
<link rel="stylesheet" type="text/css" href="{{ asset('/css/bootstrap-datepicker.min.css') }}">
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('/js/bootstrap-datepicker.ja.min.js') }}"></script>

@if (isset($friend_referral_bonus))
{{ Tag::formOpen(['url' => route('friends.update'), 'method' => 'post', 'class' => 'LockForm']) }}
@else
{{ Tag::formOpen(['url' => route('friends.create'), 'method' => 'post', 'class' => 'LockForm']) }}
@endif
@csrf
    <fieldset>
        @if (isset($friend_referral_bonus))
        <legend>スケジュール編集(お友達紹介管理)</legend>
        @else
        <legend>スケジュール追加(お友達紹介管理)</legend>
        @endif

        @if (isset($friend_referral_bonus))
        <div class="form-group">
            <label>ID</label><br />
            {{ $friend_referral_bonus['id'] }}
            {{ Tag::formHidden('id', old('id', $friend_referral_bonus['id'] ?? '')) }}
        </div>
        @endif
        <div class="form-group">
            <label for="Name">ポイント付与名称</label><br />
            {{ Tag::formText('name', old('name', $friend_referral_bonus['name'] ?? ''), ['class' => 'form-control', 'id' => 'Name']) }}<br />
        </div>
        <div class="form-group">
            <label for="rc_point">獲得条件ポイント</label><br />
            {{ Tag::formText('rc_point', old('reward_condition_point', $friend_referral_bonus['reward_condition_point'] ?? ''), ['class' => 'form-control', 'id' => 'rc_point']) }}<br />
        </div>
        <div class="form-group">
            <label for="frb_point">友達紹介報酬ポイント</label><br />
            {{ Tag::formText('frb_point', old('friend_referral_bonus_point', $friend_referral_bonus['friend_referral_bonus_point'] ?? ''), ['class' => 'form-control', 'id' => 'frb_point']) }}<br />
        </div>
        <div class="form-group">
            <label for="StAt">紹介掲載期間</label>
            <div class="form-inline">
                {{ Tag::formText('sta', old('sta', isset($friend_referral_bonus['start_at']) ? date('Y-m', strtotime(date($friend_referral_bonus['start_at']))) : date('Y-m')), ['class' => 'form-control', 'id' => 'StartAt', 'autocomplete' => 'off']) }}
                ～
                {{ Tag::formText('spa', old('spa', isset($friend_referral_bonus['stop_at']) ? date('Y-m', strtotime(date($friend_referral_bonus['stop_at']))) : date('Y-m')), ['class' => 'form-control', 'id' => 'StopAt', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <script>
            $('#StartAt').datepicker({
                language: "ja",
                autoclose: true,
                format: "yyyy-mm",
                minViewMode: 1,
                maxViewMode: 2
            });
            $('#StopAt').datepicker({
                language: "ja",
                autoclose: true,
                format: "yyyy-mm",
                minViewMode: 1,
                maxViewMode: 2
            });
        </script>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
