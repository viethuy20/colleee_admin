@extends('layouts.master')

@section('title', 'プログラムページ用キャンペーン一覧')

@section('head.load')

<script type="text/javascript">
<!--
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

<!-- プログラム複写 -->
<legend>プログラムページ用キャンペーン一覧</legend>
    @if(isset($program->id))
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
    <div>
        <legend>
        キャンペーン
       {{ Tag::link(route('program_campaigns.create',['program' => $program['id']]), '追加', ['class' => 'btn btn-info']) }}
       </legend>
    </div>
    @if(isset($campaign_list) && $campaign_list->isNotEmpty())

        <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
            <tr>
                <th style="width: 6%;">操作</th>
                <th style="width: 20%;">タイトル</th>
                <th style="width: 35%;">キャンペーン</th>
                <th style="width: 15%;">リンクURL</th>
                <th style="width: 12%;">開始日時</th>
                <th style="width: 12%;">終了日時</th>
            </tr>
            <tbody>
                @foreach ($campaign_list as $campaign)
                @php
                    $start_at = $campaign['start_at'];
                    $date_time = new DateTime($start_at);
                    $start_at_date = $date_time->format('Y-m-d');
                    $start_at_time = $date_time->format('H:i');
                    $stop_at = $campaign['stop_at'];
                    $date_time = new DateTime($stop_at);
                    $stop_at_date = $date_time->format('Y-m-d');
                    $stop_at_time = $date_time->format('H:i');

                @endphp
                <tr>
                    <td >
                        {{ Tag::link(route('program_campaigns.edit',['program_campaign' => $campaign->id]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
                    </td>
                    <td>
                        {{ $campaign['title'] }}
                    </td>

                    <td>
                        {{ $campaign['campaign'] }}
                    </td>
                    @if(isset($campaign['url'] ))
                    <td>
                        {{ $campaign['url'] }}
                    </td>
                    @else
                    <td></td>
                    @endif

                    <td>
                        {{ $start_at_date . '  ' . $start_at_time }}
                    </td>

                    <td>
                        {{ $stop_at_date . '  ' . $stop_at_time }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @endif
@endsection
