@extends('layouts.master')

@section('title', '成果管理')

@section('menu')
<li>{{ Tag::link(route('external_links.index'), 'クリック一覧') }}</li>
<li>{{ Tag::link(route('aff_rewards.index'), '成果一覧') }}</li>
@php
$aff_rewards_export_url = route('aff_rewards.export_csv');
@endphp
<li>{{ Tag::link($aff_rewards_export_url, 'CSVエクスポート') }}</li>
@if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
<li>{{ Tag::link(route('aff_rewards.import'), '成果インポート') }}</li>
<li class="active">{{ Tag::link(route('aff_rewards.achievement'), '成果レポート') }}</li>
@endif
@endsection

@php
$reward_status_map = config('reward.status');
$reward_error_map = config('reward.error');
@endphp

@section('menu.extra')

@endsection

@section('content')

<div class="row">
    <div class="col-md-3">
        <div>
            ベースレボート
        </div>
        <div>
            <select name="base" id="base" class="form-control">
                <option value="1">確定ベースレポート</option>
                <option value="2">発生ベースレポート</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div>
            対象月
        </div>
        <div>
            <select name="time" id="time" class="form-control">
                @foreach ($months as $key => $val)
                    <option value="{{ $key }}">{{ $val }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div>csvダウンロード</div>
        <div>
            <button id="btnExport" style="width: 50%" class="btn btn-primary">取得</button>
            <input type="hidden" id="url-export" value="{{ route('aff_rewards.export-achievement') }}">
        </div>
    </div>
</div>

<script type="text/javascript">
$(function() {
    $('#btnExport').on('click', function() {
        let base = $('#base').val();
        let time = $('#time').val();
        let urlExport = $('#url-export').val();
        urlExport += "?base=" + base + "&time=" + time;
        window.location.href = urlExport;
    })
})
</script>

@endsection
