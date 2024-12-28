@extends('layouts.master')

@section('title', $sp_program_type->title.'管理')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $p_sp_program_type)
<li{!! ($sp_program_type->id ==  $p_sp_program_type->id) ? ' class="active"' : '' !!}>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $p_sp_program_type]), $p_sp_program_type->title.'一覧') }}</li>
@endforeach
<li>{{ Tag::link(route('sp_programs.create', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'新規登録') }}</li>
@endsection

@section('content')
<h2>{{ $sp_program_type->title }}</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    @php
    $th_list = [['key' => 1, 'name' => 'ID',], ['key' => 0, 'name' => 'タイトル',], ['key' => 0, 'name' => '端末',],
        ['key' => 2, 'name' => '状態',], ['key' => 0, 'name' => '表示順',], ['key' => 0, 'name' => '開始日',],
        ['key' => 0, 'name' => '終了日',],];
    @endphp
    <tr>
        <th class="actions">操作</th>
        @foreach ($th_list as $th)
        <th>
            @if ($th['key'] > 0)
            {{ Tag::link($paginator->expUrl(['page' => 1, 'sort' => ($th['key'] == abs($paginator->getQuery('sort')) && $paginator->getQuery('sort') > 0) ? -$th['key'] : $th['key']]), $th['name']) }}
            @else
            {{ $th['name'] }}
            @endif
        </th>
        @endforeach
    </tr>
    @php
    $now = Carbon\Carbon::now();
    
    $device_map = config('map.device2');

    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        1 => ['class' => 'warning', 'status' => '下書き'],
        2 => ['class' => 'warning', 'status' => '公開待ち'],
        3 => ['class' => 'danger', 'status' => '公開終了'],
        4 => ['class' => 'danger', 'status' => '非公開']];
    @endphp
    @forelse ($paginator as $index => $sp_program)
    @php
    $state_id = 0;
    if ($sp_program->status == 1) {
        $state_id = 4;
    } elseif ($sp_program->status == 2) {
        $state_id = 1;
    } elseif($sp_program->start_at->gt($now)) {
        $state_id = 2;
    } elseif($sp_program->stop_at->lt($now)) {
        $state_id = 3;
    }
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions" style="white-space:nowrap">
            @if ($sp_program->status == 0 || $sp_program->status == 2)
            {{ Tag::link(route('sp_programs.edit', ['sp_program' => $sp_program]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            @endif
            @if ($sp_program->status != 0)
            {{ Tag::formOpen(['url' => route('sp_programs.enable', ['sp_program' => $sp_program])]) }}
            @csrf
            {{ Tag::formSubmit('公開', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('この".$sp_program_type->title."を公開しますか?:".$sp_program->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($sp_program->status != 1)
            {{ Tag::formOpen(['url' => route('sp_programs.destroy', ['sp_program' => $sp_program])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('非公開', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('この".$sp_program_type->title."を非公開にしますか?:".$sp_program->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td>{{ $sp_program->id }}&nbsp;</td>
        <td>{{ $sp_program->title }}&nbsp;</td>
        <td>{{ $device_map[$sp_program->devices] }}&nbsp;</td>
        <td>{{ $status_map[$state_id]['status'] }}&nbsp;</td>
        <td>{{ $sp_program->priority }}&nbsp;</td>
        <td>{{ $sp_program->start_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $sp_program->stop_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="8">{{ $sp_program_type->title }}は存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
