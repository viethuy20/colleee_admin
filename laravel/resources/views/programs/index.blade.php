@extends('layouts.master')

@section('title', 'プログラム管理')

@section('menu')
<li class="active">{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $sp_program_type)
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
@endforeach
<li>{{ Tag::link(route('programs.create'), '新規プログラム登録') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('programs.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="ProgramId">ID</label>
        {{ Tag::formText('program_id', $paginator->getQuery('program_id') ?? '', ['class' => 'form-control', 'id' => 'ProgramId']) }}
    </div>
    <div class="form-group">
        <label for="ProgramTitle">広告名</label>
        {{ Tag::formText('title', $paginator->getQuery('title') ?? '', ['class' => 'form-control', 'id' => 'ProgramTitle']) }}
    </div>
    <div class="form-group">
        <label for="ProgramStartAt">開始日</label>
        {{ Tag::formText('start_at', $paginator->getQuery('start_at') ?? '', ['class' => 'form-control', 'id' => 'ProgramStartAt']) }}
    </div>
    <div class="form-group">
        <label for="ProgramStopAt">終了日</label>
        {{ Tag::formText('stop_at', $paginator->getQuery('stop_at') ?? '', ['class' => 'form-control', 'id' => 'ProgramStopAt']) }}
    </div>
    <div class="form-group">
        <label for="AffiriateAcceptDays">獲得時期（即時）</label>
        {{ Tag::formSelect('accept_days', ['' => '---', 0 => '即時'], $paginator->getQuery('accept_days') ?? '', ['class' => 'form-control', 'id' => 'AffiriateAcceptDays']) }}
    </div>
    <div class="form-group">
        <label for="ProgramAsp">ASP</label>
        {{ Tag::formSelect('asp_id', ['0' => '---'] + $asp_map, $paginator->getQuery('asp_id') ?? 0, ['class' => 'form-control', 'id' => 'ProgramAsp']) }}
    </div>
    <div class="form-group">
        <label for="AffiriateAspAffiriateId">データ連携ID</label>
        {{ Tag::formText('asp_affiriate_id', $paginator->getQuery('asp_affiriate_id') ?? '', ['class' => 'form-control', 'id' => 'AffiriateAspAffiriateId']) }}
    </div>
    <div class="form-group">
        <label for="AffiriateAdId">ASP別検索ID</label>
        {{ Tag::formText('ad_id', $paginator->getQuery('ad_id') ?? '', ['class' => 'form-control', 'id' => 'AffiriateAdId']) }}
    </div>
    <p><b>公開状態</b></p>
    <div class="checkbox">
        <label for="ProgramEnable" class="selected">
        {{ Tag::formCheckbox('enable', 1, $paginator->getQuery('enable'), ['id' => 'ProgramEnable']) }}公開
    </div>
    <div class="form-group">{!! Tag::formSubmit('検索', ['class' => 'btn btn-default']) !!}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>プログラム</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    @php
    $th1_list = [['key' => 1, 'name' => 'ID', 'attr' => ' rowspan="2"'], ['key' => 0, 'name' => 'ASP', 'attr' => ''],
        ['key' => 2, 'name' => 'タイトル', 'attr' => ''], ['key' => 7, 'name' => '表示順', 'attr' => ' rowspan="2"'],
        ['key' => 3, 'name' => '開始日', 'attr' => ''], ['key' => 6, 'name' => '削除日', 'attr' => ''],];
    $th2_list = [['key' => 0, 'name' => '公開状態', 'attr' => ''], ['key' => 0, 'name' => '最終更新者', 'attr' => ''],
        ['key' => 4, 'name' => '終了日', 'attr' => ''], ['key' => 5, 'name' => '更新日', 'attr' => ''],];

    $now = Carbon\Carbon::now();
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        1 => ['class' => 'info', 'status' => '下書き'],
        2 => ['class' => 'warning', 'status' => '公開待ち'],
        3 => ['class' => 'danger', 'status' => '公開終了'],
        4 => ['class' => 'danger', 'status' => '非公開']];
    @endphp
    <tr>
        <th class="actions" rowspan="2">操作</th>
        @foreach ($th1_list as $th)
        <th{!! $th['attr'] !!}>
            @if ($th['key'] > 0)
            {{ Tag::link($paginator->expUrl(['page' => 1, 'sort' => ($th['key'] == abs($paginator->getQuery('sort')) && $paginator->getQuery('sort') > 0) ? -$th['key'] : $th['key']]), $th['name']) }}
            @else
            {{ $th['name'] }}
            @endif
        </th>
        @endforeach
    </tr>
    <tr>
        @foreach ($th2_list as $th)
        <th{!! $th['attr'] !!}>
            @if ($th['key'] > 0)
            {{ Tag::link($paginator->expUrl(['page' => 1, 'sort' => ($th['key'] == abs($paginator->getQuery('sort')) && $paginator->getQuery('sort') > 0) ? -$th['key'] : $th['key']]), $th['name']) }}
            @else
            {{ $th['name'] }}
            @endif
        </th>
        @endforeach
    </tr>
    @forelse ($paginator as $index => $program)
    @php
    $affiriate = $program->affiriates()->ofEnable()->first();
    $point = $program->points()->ofEnable()->first();

    $state_id = 0;
    if ($program->status == 1) {
        $state_id = 4;
    } elseif ($program->status == 2) {
        $state_id = 1;
    } elseif($program->start_at->gt($now)) {
        $state_id = 2;
    } elseif($program->stop_at->lt($now)) {
        $state_id = 3;
    }
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions" style="white-space:nowrap" rowspan="2">
            {{ Tag::link(route('programs.edit', ['program' => $program]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            {{ Tag::link(route('program_campaigns.index', ['program' => $program]), 'キャンペーン', ['class' => 'btn btn-small btn-info']) }}<br />
            {{ Tag::link(route('programs.edit', ['program' => $program]).'#point', 'ポイント', ['class' => 'btn btn-small btn-info']) }}<br />
            @if (in_array(190, $program->label_id_list, false))
            {{ Tag::link(route('credit_cards.edit', ['program' => $program]), 'クレジットカード', ['class' => 'btn btn-small btn-info']) }}<br />
            @endif
            @if ($program->status != 0)
            {{ Tag::formOpen(['url' => route('programs.enable', ['program' => $program])]) }}
            @csrf
            {{ Tag::formSubmit('公開', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('このプログラムを公開しますか?:".$program->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($program->status != 1)
            {{ Tag::formOpen(['url' => route('programs.destroy', ['program' => $program])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('非公開', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('このプログラムを非公開にしますか?:".$program->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td rowspan="2">{{ $program->id }}&nbsp;</td>
        <td>{{ isset($affiriate->asp_id) ? $asp_map[$affiriate->asp_id] : '' }}&nbsp;</td>
        <td>{{ $program->title }}&nbsp;</td>
        <td rowspan="2">{{ $program->priority }}&nbsp;</td>
        <td>{{ $program->start_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ isset($program->deleted_at) ? $program->deleted_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td>{{ $status_map[$state_id]['status'] }}&nbsp;</td>
        <td>{{ isset($program->admin->email) ? $program->admin->email : '' }}&nbsp;</td>
        <td>{{ $program->stop_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ isset($program->updated_at) ? $program->updated_at->format('Y-m-d H:i:s') : $program->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="7">プログラムは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
