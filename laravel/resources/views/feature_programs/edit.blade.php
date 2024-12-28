@extends('layouts.master')

@section('title', '新規特集広告登録')

@section('head.load')
<script type="text/javascript"><!--
$(document).on('change', ".AjaxCategory", function(event) {
    var elementId = $(this).attr('forRender');
    var ajaxUrl = $(this).attr('forUrl');
    var featureId = $("#FeatureProgramFeatureId option:selected").val();

    $.ajax({
        type: 'GET',
        data: {
            feature_id : featureId
        },
        url: ajaxUrl,
        scriptCharset: 'utf-8',
        dataType: 'json',
        beforeSend: function (xhr) {
            var token = $('meta[name="csrf_token"]').attr('content');
            if (token) {
                return xhr.setRequestHeader('X-XSRF-TOKEN', token);
            }
        },
        success: function(data) {
            // 特集サブカテゴリドロップダウンリスト更新
            $('#FeatureProgramSubCategory option').remove();
            $.each(data, function(key, array) {
                $('#FeatureProgramSubCategory').append($('<option>').text('ピックアップ').attr('value', 0));
                $.each(array, function(id, title) {
                    $('#FeatureProgramSubCategory').append($('<option>').text(title).attr('value', id));
                });
            });
        },
        error: function(xhr) {
            $('#' + elementId).html('データ取得に失敗しました');
        }
    });
});

$(document).on('input', ".AjaxProgram", function(event) {
    var elementId = $(this).attr('forRender');
    var ajaxUrl = $(this).attr('forUrl');
    var programId = $("#FeatureProgramProgram").val();

    $.ajax({
        type: 'GET',
        data: {
            program_id : programId
        },
        url: ajaxUrl,
        scriptCharset: 'utf-8',
        dataType: 'json',
        beforeSend: function (xhr) {
            var token = $('meta[name="csrf_token"]').attr('content');
            if (token) {
                return xhr.setRequestHeader('X-XSRF-TOKEN', token);
            }
        },
        success: function(data) {
            $('#DisableProgramError').html('');
            $('#ProgramTitle').html(data['title']);
        },
        error: function(xhr) {
            $('#ProgramTitle').html('');
            $('#DisableProgramError').html('無効なプログラムIDです');
        }
    });
});

$(function()　{
    tinyMCE.init({
        mode: "textareas",
        selector:"#FeatureProgramDetail",
        language: "ja",
        toolbar: "forecolor link bullist image code",
        plugins: "textcolor link lists image imagetools code autoresize",
        fullpage_default_doctype: "",
        fullpage_default_encoding: "UTF-8",
        menubar: false,
        statusbar: false,
        cleanup : false,
        
        force_br_newlines : true,
        force_p_newlines : false,
        forced_root_block : '',
        
        document_base_url : "{{ config('app.client_url') }}",
        convert_urls : false,
        file_picker_callback : function(callback, value, meta) {
            imageFilePicker(callback, value, meta);
        },
        imagetools_toolbar: "rotateleft rotateright | flipv fliph | editimage imageoptions"
    });
});
//-->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('feature_programs.index'), '特集広告一覧') }}</li>
<li{!! (isset($feature_program['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('feature_programs.create'), '新規特集広告登録') }}</li>
@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('feature_programs.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($feature_program['id']))
        <legend>特集広告更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $feature_program['id'] }}
            {{ Tag::formHidden('id', $feature_program['id']) }}
        </div>
        @else
        <legend>特集広告作成</legend>
        @endif
        
        <div class="form-group AjaxCategory" forUrl="{{ route('feature_programs.sub_category') }}" forRender="sub_category_list">
            <label for="FeatureProgramFeatureId">特集カテゴリ</label><br />
            {{ Tag::formSelect('feature_id', $feature_category_map, $feature_program['feature_id'] ?? '', ['class' => 'form-control', 'id' => 'FeatureProgramFeatureId']) }}<br />
        </div>

        <div class="form-group">
            <label for="FeatureProgramSubCategory">特集サブカテゴリ</label><br />
            {{ Tag::formSelect('sub_category_id', [0 => 'ピックアップ'] + $sub_category_map, $feature_program['sub_category_id'] ?? '', ['class' => 'form-control', 'id' => 'FeatureProgramSubCategory']) }}<br />
        </div>

        <div class="form-group AjaxProgram" forUrl="{{ route('programs.enable_program') }}" forRender="program">
            <label for="FeatureProgramProgram">プログラム</label><br />
            <div id="ProgramTitle">{{ $feature_program['program']->title ?? ''}}</div>
            {{ Tag::formText('program_id', old('program_id', $feature_program['program_id'] ?? ''), ['class' => 'form-control', 'id' => 'FeatureProgramProgram']) }}
            <div id="DisableProgramError"></div><br />
        </div>

        <div class="form-group">
            <label for="FeatureProgramDetail">詳細</label><br />
            {{ Tag::formTextarea('detail', old('detail', $feature_program['detail'] ?? ''), ['class' => 'form-control', 'rows' => 5, 'id' => 'FeatureProgramDetail']) }}<br />
        </div>

        <div class="form-group">
            <label for="FeatureProgramPriority">表示順</label><br />
            {{ Tag::formNumber('priority', old('priority', $feature_program['priority']), ['class' => 'form-control', 'required' => 'required', 'id' => 'FeatureProgramPriority']) }}<br />
        </div>

        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
