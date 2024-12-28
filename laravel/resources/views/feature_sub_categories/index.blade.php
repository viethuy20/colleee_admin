@extends('layouts.master')

@section('title', '特集サブカテゴリ一覧')

@section('menu')
<li class="active">{{ Tag::link(route('feature_sub_categories.index'), '特集サブカテゴリ一覧') }}</li>
<li>{{ Tag::link(route('feature_sub_categories.create'), '新規特集サブカテゴリ登録') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('feature_sub_categories.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group AjaxContent" forUrl="{{ route('feature_programs.sub_category') }}" forRender="sub_category_list">
        <label for="FeatureSubCategoryFeatureId">特集カテゴリ</label>
        {{ Tag::formSelect('feature_id', ['' => '---'] + $feature_category_map, $paginator->getQuery('feature_id') ?? '', ['class' => 'form-control', 'id' => 'FeatureSubCategoryFeatureId']) }}<br />
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>特集サブカテゴリ</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>操作</th>
        <th>ID</th>
        <th>特集カテゴリ</th>
        <th>タイトル</th>
        <th>遷移先URL</th>
        <th>表示順</th>
        <th>作成日時</th>
        <th>更新日時</th>
    </tr>

    @forelse ($paginator as $index => $feature_sub_category)
    <tr>
        <td class="actions" style="white-space:nowrap">
            {{ Tag::link(route('feature_sub_categories.edit', ['feature_sub_category' => $feature_sub_category]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
        </td>
        <td>{{ $feature_sub_category->id }}&nbsp;</td>
        <td>{{ isset($feature_sub_category->feature_id) ? $feature_category_map[$feature_sub_category->feature_id] : '' }}&nbsp;</td>
        <td>{{ $feature_sub_category->title }}&nbsp;</td>
        <td>{{ $feature_sub_category->url }}&nbsp;</td>
        <td>{{ $feature_sub_category->priority }}&nbsp;</td>
        <td>{{ $feature_sub_category->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $feature_sub_category->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="7">特集サブカテゴリは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection