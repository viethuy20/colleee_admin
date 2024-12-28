@extends('layouts.master')

@section('title', 'お友達紹介管理')
@section('menu')
<li>{{ Tag::link(route('review_point_management.index'), 'ポイント管理一覧') }}</li>
<li>{{ Tag::link(route('review_point_management.create'), 'ポイント管理追加') }}</li>
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

@if (isset($referral_bonus))
{{ Tag::formOpen(['url' => route('review_point_management.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@else
{{ Tag::formOpen(['url' => route('review_point_management.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@endif
@csrf
    <fieldset>
        @if (isset($referral_bonus))
        <legend>スケジュール編集(お友達紹介管理)</legend>
        @else
        <legend>配布ポイント追加</legend>
        @endif

        @if (isset($referral_bonus))
        <div class="form-group">
            <label>ID</label><br />
            {{ $referral_bonus['id'] }}
            {{ Tag::formHidden('id', old('id', $referral_bonus['id'] ?? '')) }}
        </div>
        @endif
        <div class="form-group">
            <label for="rc_point">口コミ配布ポイント数</label><br />
            {{ Tag::formText('rc_point', old('reward_condition_point', $referral_bonus['reward_condition_point'] ?? ''), ['class' => 'form-control', 'id' => 'rc_point']) }}<br />
        </div>
        <div class="form-group">
            <label for="StAt">実施期間（開始日）</label>
            <div class="form-inline">
            {{ Tag::formText('sta', old('sta', isset($referral_bonus['start_at']) ? date('Y-m-d', strtotime(date($referral_bonus['start_at']))) : date('Y-m-d')), ['class' => 'form-control', 'id' => 'StartAt', 'autocomplete' => 'off']) }}
            ～
            </div>
        </div>
        <script>
            $('#StartAt').datepicker({
                language: "ja",
                autoclose: true,
                format: "yyyy-mm-dd",
                minViewMode: 0,
            });
        </script>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
