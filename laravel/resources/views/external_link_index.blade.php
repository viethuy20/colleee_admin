@extends('layouts.master')

@section('title', 'クリック一覧')

@section('menu')
<li class="active">{{ Tag::link(route('external_links.index'), 'クリック一覧') }}</li>
<li>{{ Tag::link(route('aff_rewards.index'), '成果一覧') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('external_links.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="ExternalLinkUserName">ユーザーID</label>
        {{ Tag::formText('user_name', $paginator->getQuery('user_name') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkUserName']) }}
    </div>
    <div class="form-group">
        <label for="ExternalLinkProgramId">プログラムID</label>
        {{ Tag::formText('program_id', $paginator->getQuery('program_id') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkProgramId']) }}
    </div>
    <div class="form-group">
        <label for="ExternalLinkTitle">プログラム名</label>
        {{ Tag::formText('title', $paginator->getQuery('title') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkTitle']) }}
    </div>
    <div class="form-group">
        <label for="ExternalLinkAspId">ASP</label>
        {{ Tag::formSelect('asp_id', ['' => '---'] + $asp_map, $paginator->getQuery('asp_id') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkAspId']) }}
    </div>
    <div class="form-group">
        <label for="ExternalLinkAspAffiliateId">データ連携ID</label>
        {{ Tag::formText('asp_affiliate_id', $paginator->getQuery('asp_affiliate_id') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkAspAffiliateId']) }}
    </div>
    <div class="form-group">
        <label for="ExternalLinkIp">IP</label>
        {{ Tag::formText('ip', $paginator->getQuery('ip') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkIp']) }}
    </div>
    <div class="form-group">
        <label for="ExternalLinkStartAt">期間</label>
        <div class="form-inline">
            {{ Tag::formText('start_at', $paginator->getQuery('start_at') ?? '', ['class' => 'form-control', 'id' => 'ExternalLinkStartAt']) }}
            ～
            {{ Tag::formText('end_at', $paginator->getQuery('end_at') ?? '', ['class' => 'form-control']) }}
        </div>
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>クリック一覧</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th rowspan="2">ID</th>
        <th>ユーザーID</th>
        <th>プログラムID</th>
        <th>プログラム名</th>
        <th>RID</th>
        <th>UA</th>
        <th>IP</th>
    </tr>
    <tr>
        <th>予想ユーザーID</th>
        <th>ASP</th>
        <th>データ連携ID</th>
        <th colspan="2">URL</th>
        <th>日時</th>
    </tr>
    @php
    $now = Carbon\Carbon::now();
    @endphp
    @forelse ($paginator as $index => $external_link)
    @php
    $user = $external_link->user;
    $program = $external_link->program;
    @endphp
    <tr>
        <td rowspan="2">{{ $external_link->id }}&nbsp;</td>
        <td>{{ $external_link->user_name }}&nbsp;</td>
        <td>{{ $program->id ?? '' }}&nbsp;</td>
        <td>{{ $program->title ?? '' }}&nbsp;</td>
        <td>{{ $external_link->rid ?? '' }}&nbsp;</td>
        <td class="col-md-4" style="word-break: break-all;">{{ $external_link->ua }}&nbsp;</td>
        <td>{{ $external_link->ip }}&nbsp;</td>
    </tr>
    <tr>
        <td>{{ $user->old_id ?? '' }}&nbsp;</td>
        <td>{{ $asp_map[$external_link->asp_id] ?? '不明' }}&nbsp;</td>
        <td>{{ $external_link->asp_affiliate_id ?? '' }}&nbsp;</td>
        <td colspan="2" class="col-md-6" style="word-break: break-all;">{{ $external_link->url }}&nbsp;</td>
        <td>{{ $external_link->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="7">クリックは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
