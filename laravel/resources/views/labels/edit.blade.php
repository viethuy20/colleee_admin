@extends('layouts.ajax')

@section('head.load')
<script type="text/javascript">
<!--
$(function(){
// タグコピー
    $('.tagCopy').on('click', function(event) {
        // イベント停止
        event.preventDefault();

        var tagTextarea = $('#LabelTags');
        var str_arr = tagTextarea.val().split(',');
        var v = $(this).text();
        var index = str_arr.indexOf(v);
        if (index > -1) {
            return false;
        }
        str_arr.push(v);
        str_arr = str_arr.filter(function (element, index, self) {
            if (element.trim() === '') { return false; }
            return self.indexOf(element) === index;
        });
        $('#LabelTags').val(str_arr.join(','));
        var a = $("<a></a>", {href: "#", class: "tagDelete"});
        a.text(v);
        $('#TagDelete').append(a).append('<br>');
        return false;
    });

});
// タグ削除
$(document).on('click', '.tagDelete', function(event) {
    // イベント停止
    event.preventDefault();
     var tagTextarea = $('#LabelTags');
    var str_arr = tagTextarea.val().split(',');
    var v = $(this).text();
    var index = str_arr.indexOf(v);
    if (index < 0) {
        return false;
    }
    str_arr.splice(index, 1);
    str_arr = str_arr.filter(function (element, index, self) {
        if (element.trim() === '') { return false; }
        return self.indexOf(element) === index;
    });
    $('#LabelTags').val(str_arr.join(','));
    $(this).next().remove();
    $(this).remove();
  
    return false;
});
// -->
</script>

@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('labels.store'), 'method' => 'post', 'files' => true, 'id' => 'LabelForm']) }}
@csrf    
{{ Tag::formHidden('id', $target_label->id, ['id' => 'LabelId']) }}
    <fieldset>
        <div id="input" title="入力ダイアログ" class="form-group" forUrl="{{ config('app.url' )}}">
            <div class="form-group">
                <label>名称</label><br />
                {{ Tag::formText('name', $target_label->name, ['class' => 'form-control', 'id' => 'InputName']) }}
                {{ $errors->has('name') ? $errors->first('name') : '' }}
            </div>
            <p><b>タグ</b></p>
            <div id="TagDelete" class="well">
                @php
                $ptags = old('tags', $target_label->tags);
                $tag_list = explode(',', $ptags);
                @endphp
                @foreach ($tag_list as $tag)
                {{ Tag::link('#', $tag, ['class' => 'tagDelete']) }}</br>
                @endforeach
                {{ Tag::formHidden('tags', $ptags, ['id' => 'LabelTags']) }}
            </div>
            <p><b>タグ候補</b></p>
            <div id="TagCopy" class="well">
                @foreach ($high_use_tag_list as $tag)
                {{ Tag::link('#', $tag, ['class' => 'tagCopy']) }}
                @endforeach
            </div>
            <div id="result" class="danger"></div>
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection