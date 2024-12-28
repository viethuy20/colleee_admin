@extends('layouts.master')

@section('title', 'タグ管理')

@section('head.load')
<script type="text/javascript">
<!--
$(function(){
    // タグコピー
    $('.tagCopy').on('click', function(event) {
        // イベント停止
        event.preventDefault();

        var tagTextarea = $('#TagTags');
        tagTextarea.focus();
        var str_arr = tagTextarea.val().split(',');
        var v = $(this).text()
        str_arr.push(v);
        str_arr = str_arr.filter(function (element, index, self) {
            if (element.trim() === '') { return false; }
            return self.indexOf(element) === index;
        });
        $('#TagTags').val(str_arr.join(','));
        return false;
    });
});
// -->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
<li{!! (isset($tag['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('tags.create'), '新規タグ登録') }}</li>
@endsection

@section('content')

{{ Tag::formOpen(['url' => route('tags.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($tag['id']))
        <legend>タグ更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $tag['id'] }}
            {{ Tag::formHidden('id', $tag['id']) }}
        </div>
        @else
        <legend>タグ作成</legend>
        @endif
	<div class="form-group">
            <label for="TagName">名称</label>
            {{ Tag::formText('name', $tag['name'] ?? '', ['class' => 'form-control', 'maxlength' => '30', 'required' => 'required', 'id' => 'TagName']) }}<br />
            {{ $errors->has('name') ? $errors->first('name') : '' }}
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
