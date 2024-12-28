@extends('layouts.master')

@section('title', '新規特集サブカテゴリ登録')

@section('menu')
<li>{{ Tag::link(route('feature_sub_categories.index'), '特集サブカテゴリ一覧') }}</li>
<li{!! (isset($feature_sub_category['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('feature_sub_categories.create'), '新規特集サブカテゴリ登録') }}</li>
@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('feature_sub_categories.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($feature_sub_category['id']))
        <legend>特集サブカテゴリ更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $feature_sub_category['id'] }}
            {{ Tag::formHidden('id', $feature_sub_category['id']) }}
        </div>
        @else
        <legend>特集サブカテゴリ作成</legend>
        @endif
        
        <div class="form-group">
            <label for="FeatureSubCategoryFeatureId">特集カテゴリ</label><br />
            @if (isset($feature_sub_category['id']))
            {{ $feature_category_map[$feature_sub_category['feature_id']] }}
            {{ Tag::formHidden('feature_id', $feature_sub_category['feature_id']) }}
            @else
            {{ Tag::formSelect('feature_id', $feature_category_map, '', ['class' => 'form-control', 'id' => 'FeatureSubCategoryFeatureId']) }}<br />
            @endif
        </div>

        <div class="form-group">
            <label for="FeatureSubCategoryTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $feature_sub_category['title'] ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'FeatureSubCategoryTitle']) }}<br />
        </div>

        <div class="form-group">
            <label for="FeatureSubCategoryUrl">遷移先URL</label><br />
            {{ Tag::formText('url', old('url', $feature_sub_category['url']), ['class' => 'form-control', 'id' => 'FeatureSubCategoryUrl']) }}<br />
        </div>

        <div class="form-group">
            <label for="FeatureSubCategoryPriority">表示順</label><br />
            {{ Tag::formNumber('priority', old('priority', $feature_sub_category['priority']), ['class' => 'form-control', 'required' => 'required', 'id' => 'FeatureSubCategoryPriority']) }}<br />
        </div>

        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
