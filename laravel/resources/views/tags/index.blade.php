@extends('layouts.master')

@section('title', 'タグ管理')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li class="active">{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php 
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $sp_program_type)
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
@endforeach
<li>{{ Tag::link(route('tags.create'), '新規タグ登録') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('tags.index'), 'method' => 'get']) }}
@csrf    
{{ Tag::formHidden('sort', $paginator->getQuery('sort')) }}
    <div class="form-group">
        <label for="TagName">名称</label>
        {{ Tag::formText('name', $paginator->getQuery('name') ?? '', ['class' => 'form-control', 'id' => 'TagName']) }}
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>タグ</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        @php
        $th_list = [['key' => 1, 'name' => '名称'], ['key' => 2, 'name' => 'プログラム登録数'],
            ['key' => 3, 'name' => '作成日時'], ['key' => 4, 'name' => '更新日時']];
        @endphp
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
    
    @forelse ($paginator as $index => $tag)
    <tr>
        <td class="actions" style="white-space:nowrap">
            {{ Tag::link(route('tags.edit', ['tag' => $tag]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
        </td>
        <td>
            {{ $tag->name }}<br />
        </td>
        <td>{{ number_format($tag->program_total) }}&nbsp;</td>
        <td>{{ $tag->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $tag->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="5">タグは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
