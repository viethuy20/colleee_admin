@extends('layouts.master')

@section('title', 'プログラムページ用キャンペーン登録')

@section('head.load')

<script type="text/javascript">
<!--

//datetime picker
$( function() {
    $( "#CampaignStartAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
    $( "#CampaignStopAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });

  } );


// -->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
@if (isset($program['id']))
<li>{{ Tag::link(route('program_campaigns.index', ['program' => $program]), 'キャンペーン一覧') }}</li>
@endif
@endsection

@section('menu.extra')
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
if(isset($campaign->id)){
    $start_at = $campaign['start_at'];
    $date_time = new DateTime($start_at);
    $start_at_date = $date_time->format('Y-m-d');
    $start_at_time = $date_time->format('H:i');
    $stop_at = $campaign['stop_at'];
    $date_time = new DateTime($stop_at);
    $stop_at_date = $date_time->format('Y-m-d');
    $stop_at_time = $date_time->format('H:i');
}

@endphp
<!-- プログラム複写 -->

{{ Tag::formOpen(['url' => route('program_campaigns.store'), 'method' => 'post', 'files' => true, 'id' => 'LockFormId', 'class' => 'LockForm']) }}
@csrf
<fieldset>
    <legend>
        @if (isset($campaign->id))
        プログラムページ用キャンペーン編集
        {{ Tag::formHidden('id', $campaign->id) }}

        @else
        プログラムページ用キャンペーン登録
        @endif
        {{ Tag::formHidden('program_id', $program->id) }}

    </legend>
    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
        <tr>
            <th>ID</th>
            <th>タイトル</th>
        </tr>
        <tbody>
            <tr>
                <td>
                    {{ $program->id }}
                </td>
                <td>
                    {{ $program->title }}
                </td>
            </tr>
        </tbody>
    </table>

        <div id="CampaignDetail">
                <div class="question-group">
                    <div class="form-group">
                        <label for="campaignTilte">タイトル</label>
                        {{ Tag::formText('campaigns[title]', old('title',  isset($campaign['title']) ? $campaign['title'] : $program->title), ['class' => 'form-control', 'required' => 'required', 'id' => 'ProgramTitle']) }}
                    </div>

                    <div class="form-group">
                        <label for="campaignText">キャンペーン</label>
                        {{ Tag::formText('campaigns[campaign]', old('campaign', $campaign['campaign'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'CampaignText']) }}
                    </div>
                    <div class="form-group">
                        <label for="campaignUrl">リンクURL</label>
                        {{ Tag::formText('campaigns[url]', old('url', $campaign['url'] ?? ''), ['class' => 'form-control']) }}
                    </div>
                    <div class="form-group">
                        <label for="campaignStart">開始日時</label>
                        <div class="form-inline">
                            {{ Tag::formText('campaigns[start_at_date]', old('start_at_date', $start_at_date ?? now()->format('Y-m-d')), ['class' => 'form-control', 'id' => 'CampaignStartAt']) }}
                            {{ Tag::formTime('campaigns[start_at_time]', old('start_at_time', $start_at_time ?? now()->format('H:i')), ['class' => 'form-control']) }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="CampaignStop">終了日時</label>
                        <div class="form-inline">
                            {{ Tag::formText('campaigns[stop_at_date]', old('stop_at_date', $stop_at_date ?? '9999-12-31'), ['class' => 'form-control', 'id' => 'CampaignStopAt']) }}
                            {{ Tag::formTime('campaigns[stop_at_time]', old('stop_at_time', $stop_at_time ?? '23:59'), ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
        </div>
        <div class="form-group">{{ Tag::formSubmit('保存する', ['class' => 'btn btn-default btn-lg']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
