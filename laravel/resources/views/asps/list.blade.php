@extends('layouts.master')

@section('title', 'ASP管理')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li class="active">{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php 
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $sp_program_type)
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
@endforeach
@endsection

@section('content')
<h2>ASP</h2>

<table cellpadding="0" cellspacing="0" class="table">
    <tr>
        <th class="actions">操作</th>
        <th>ID</th>
        <th>名称</th>
        <th>企業</th>
    </tr>

    @forelse ($asp_list as $asp)
    <tr>
        <td class="actions">
            {{ Tag::link(route('asps.edit', ['asp' => $asp]), '編集', ['class' => 'btn btn-small btn-success']) }}
        </td>
        <td>{{ $asp->id }}&nbsp;</td>
        <td>{{ $asp->name }}&nbsp;</td>
        <td>{{ $asp->company }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="4">ASPは存在しません</td></tr>
    @endforelse
</table>
@endsection
