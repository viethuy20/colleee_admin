@extends('layouts.master')

@section('title', 'アフィリエイト管理')

@section('head.load')
<script type="text/javascript">
$(function(){
    // 画像URL処理
    $('.fileUrl').on('change', function(event) {
        var img = $('#' + $(this).attr('forImg'));
        // 読み込んだデータをimgに設定
        img.attr('src', $(this).val());
        // 表示
        img.show();
    });
});
//対象デバイス => タイトル
$(document).on('change', '#AffiriateAspId', function(event) {
    event.preventDefault();
    // check status of checkboxes
    let id = $(this).val()
    changeUrl(id)
});

function changeUrl(id) {
    $.ajax({
        type: 'GET',
        data: {id: id},
        url: "{{ route('programs.get_url') }}",
        scriptCharset: 'utf-8',
        dataType: 'json',
        beforeSend: function (xhr) {
            var token = $('meta[name="csrf_token"]').attr('content');
            if (token) {
                return xhr.setRequestHeader('X-XSRF-TOKEN', token);
            }
        },
        success: function(data) {
            if (data['error'] || data['url']==null) {
                $('#ajaxAffiriateUrl').html('ユーザーID:{{ App\Affiriate::COLLEEE_USERID_REPLACE }}<br />RID:{{ App\Affiriate::COLLEEE_RID_REPLACE }}<br />');
                return;
            }
            else {
                $('#ajaxAffiriateUrl').text(data['url']);
                return;
            }
        },
    });
    return false;
}
// -->
//datetime picker
$( function() {
    $( "#AffiriateStartAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });

  } );
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('programs.edit', ['program' => $program]), 'プログラム更新') }}</li>
@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>プログラム情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>タイトル</th></tr>
    <tr><td>{{ $program->id }}</td><td>{{ $program->title }}</td></tr>
</table>

@include('elements.attachment_select', ['img_ids_id' => 'ProgramImgIds', 'parent_type' => 'program', 'parent_id' => $program->id])

{{ Tag::formOpen(['url' => route('affiriates.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
{{ Tag::formHidden('parent_id', $program->id) }}
    <fieldset>
        @if (isset($affiriate['id']))
        <legend>アフィリエイト更新</legend>
        {{ Tag::formHidden('id', old('id', $affiriate['id'] ?? '')) }}
        @else
        <legend>アフィリエイト作成</legend>
        @endif
        @php
        asort($asp_map);
        @endphp
        <div class="form-group">
            <label for="AffiriateAspId">ASP</label><br />
            @php
            $asp_id = old('asp_id', $affiriate['asp_id']);
            @endphp
            @if ($affiriate['editable'])
            {{ Tag::formSelect('asp_id', ['' => '---'] +  $asp_map, $asp_id ?? '', ['class' => 'form-control', 'required' => 'required', 'id' => 'AffiriateAspId']) }}
            @else
            {{ $asp_map[$asp_id] }}<br />
            {{ Tag::formHidden('asp_id', $asp_id) }}
            @endif
        </div>
        <div class="form-group">
            <label for="AffiriateAspAffiriateId">データ連携ID</label><br />
            {{ Tag::formText('asp_affiriate_id', old('asp_affiriate_id', $affiriate['asp_affiriate_id'] ?? ''), ['class' => 'form-control', 'id' => 'AffiriateAspAffiriateId']) }}<br />
        </div>
        <div class="form-group">
            <label for="AffiriateAdId">ASP別検索ID</label><br />
            {{ Tag::formText('ad_id', old('ad_id', $affiriate['ad_id'] ?? ''), ['class' => 'form-control', 'id' => 'AffiriateAdId']) }}<br />
        </div>
        <div class="form-group">
            <label for="AffiriateUrl">遷移先URL</label><br />
            {{ Tag::formText('url', old('url', $affiriate['url'] ?? ''), ['class' => 'form-control', 'id' => 'AffiriateUrl']) }}<br />
            <span id="ajaxAffiriateUrl">ユーザーID:{{ App\Affiriate::COLLEEE_USERID_REPLACE }}<br />RID:{{ App\Affiriate::COLLEEE_RID_REPLACE }}<br /></span>
        </div>
        <div class="form-group">
            <label for="AffiriateImgUrl">画像URL</label><br />
            @php
            $img_id = 'AffiriateImg';
            $img_url = old('img_url', $affiriate['img_url'] ?? null);
            @endphp
            @if (isset($img_url))
            {{ Tag::image($img_url, 'img', ['id' => $img_id, 'width' => '120px']) }}
            @else
            <img id="{{ $img_id }}" alt="img" width="120px" style="display:none" />
            @endif
            {{ Tag::formText('img_url', $img_url, ['class' => 'form-control fileUrl', 'maxlength' => '256', 'id' => 'AffiriateImgUrl', 'forImg' => $img_id]) }}<br />
            <input type="button" onclick="openImageDialog('{{ $img_id }}', 'AffiriateImgUrl');" value="参照" /><br />
        </div>
        <div class="form-group">
            <label for="AffiriateGiveDays">予定反映目安</label><br />
            {{ Tag::formNumber('give_days', old('give_days', $affiriate['give_days'] ?? null), ['class' => 'form-control', 'id' => 'AffiriateGiveDays']) }}<br />
        </div>
        <div class="form-group">
            <label for="AffiriateAcceptDays">獲得時期目安</label><br />
            <div class="form-inline">
                {{ Tag::formCheckbox('accept_speedy', 1, 0) }}即時
                {{ Tag::formSelect('accept_days', ['' => '---'] + config('map.accept_days'), old('accept_days', $affiriate['accept_days'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'AffiriateAcceptDays']) }}
            </div>
        </div>
        <div class="form-group">
            <div class="form-inline">
            <label for="AffiriateStartAt">掲載期間</label><br />
                @php
                $start_at_date = old('start_at_date', $affiriate['start_at_date'] ?? '');
                $start_at_time = old('start_at_time', $affiriate['start_at_time'] ?? '');
                @endphp
                @if ($affiriate['editable'])
                {{ Tag::formText('start_at_date', $start_at_date, ['class' => 'form-control', 'id' => 'AffiriateStartAt']) }}
                {{ Tag::formTime('start_at_time', $start_at_time, ['class' => 'form-control']) }}
                @else
                {{ $start_at_date .' '. $start_at_time }}
                @endif
                ～
                @if ($affiriate['stop_at'] != '9999-12-31 23:59')
                {{ $affiriate['stop_at'] }}
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="AffiriateMemo">Memo</label>
            {{ Tag::formTextarea('memo', old('memo', $affiriate['memo'] ?? null), ['class' => 'form-control', 'rows' => 3, 'id' => 'AffiriateMemo']) }}<br />
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
