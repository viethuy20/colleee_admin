@extends('layouts.image')

@section('head.load')

<style>
.thumbnail {
    display: inline-block;
    background-color: #ccc;
    background-position: center center;
    background-repeat: no-repeat;
    margin: 5px;
    height: 100px;
    border: 1px solid #ccc;
    background-size: contain;
}
.clickDetail {
    display: inline-block;
    _display: inline;
    
    border-style: solid;
    border-width: 1px;
    margin: 5px 5px 5px 5px;
    padding: 10px 10px 10px 10px;
    border-color: #ccc;
    background-color: #f1f1f1;
    border-radius: 10px;
}
</style>

<script type="text/javascript"><!--
var setImageUrl = function(url, img_ids) {
    @if($type == 'tinymce')
    parent.tinymce.activeEditor.windowManager.getParams().oninsert(url, img_ids);
    parent.tinymce.activeEditor.windowManager.close();
    @endif
    @if($type == 'default')
    parent.myImage.oninsert(url, img_ids);
    parent.myImage.close();
    @endif
}
    
var setFileName = function(fileButton) {
    if(!fileButton.files.length) { return; }
    var file=fileButton.files[0];
    var fileName = $('#UploadFileName');
    if (fileName.val() != '') { return; }
    fileName.val(file.name);
};
var setAjaxForm = function() {
    $('.ajaxForm').submit(function(event) {
        /* stop form from submitting normally */
        event.preventDefault();

        var ajaxUrl = $(this).attr('action');
        var method = $(this).attr('method').toUpperCase();
        
        var postData = new FormData($(this).get(0));
        
        $.ajax({
            type: method,
            url: ajaxUrl,
            scriptCharset: 'utf-8',
            data: postData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function ( xhr ) {
                var token = $('meta[name="csrf-token"]').attr('content');
                if (token) {
                    return xhr.setRequestHeader('X-XSRF-TOKEN', token);
                }
            },
            success: function(data) {
                if (!data || typeof data.location != 'string' || !data.img_ids) { return; }
                setImageUrl(data.location, data.img_ids);
            },
            error:function(xhr, textStatus, errorThrown) {
                $('#AttachmentsMessage').html('失敗しました' + '<br />' + "XMLHttpRequest : " + xhr.status + ",textStatus : " + textStatus + ",errorThrown : " + errorThrown.message);
            }
        });
    });
}

$(function(){
    // フォームをAjax化
    setAjaxForm();
    
    $('.clickDetail').click(function() {
        var url = $(this).attr('rurl');
        setImageUrl(url);
    });
    // 画像ファイル処理
    $('.fileInput').on('change', function(event) {
        var lg = $(this)[0].files.length;
        var items = $(this)[0].files;
        if (lg > 0) {
            var size = 0;
            for (var i = 0; i < lg; i++) {
	        size = size + items[i].size; // ファイルサイズを取得
	    }
            if (size > 0) {
                var form = $('#AttachmentsUploadAjaxform');
                form.submit();
            }
	}
    });
});
// --></script>

@endsection

@section('content')

<div id="AttachmentsMessage"></div>
{!! Tag::formOpen(['url' => route('attachments.upload'), 'files' => true , 'class' => 'ajaxForm', 'id' => 'AttachmentsUploadAjaxform']) !!}
@csrf
@if (isset($parent_type) && isset($parent_id))
{!! Tag::formHidden('parent_type', $parent_type) !!}
{!! Tag::formHidden('parent_id', $parent_id) !!}
@endif
{!! Tag::formFile('file', ['enctype' => 'multipart/form-data', 'class' => 'fileInput']) !!}<br />
{!! Tag::formClose() !!}

<div>
@forelse ($img_list as $img)
<div class="clickDetail" rurl="{{ $img->full_url }}" >
    {!! Tag::image($img->full_url, null, ['class' => 'thumbnail']) !!}<br />
    {{ $img->full_url }}
</div>
@empty
画像素材は存在しません
@endforelse
</div>

@endsection