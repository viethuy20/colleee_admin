@extends('layouts.master')

@section('title', 'プログラム管理')

@section('head.load')

<script type="text/javascript">
<!--
$(function(){
    tinyMCE.init({
        mode: "textareas",
        selector:"#ProgramDetail,#AdDetail",
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

    @if (!isset($program['id']))
    const scheduleTagId = 'ProgramScheduleRewardCondition';
    $(`[id^=${scheduleTagId}]`).each(function(element) {
        const index = $(this).attr('id').match(/\d+$/)[0];
        bindSchedule(scheduleTagId, index);
    });

    const courseTagId = 'CourseName';
    $(`[id^=${courseTagId}`).each(function(element) {
        const index = $(this).attr('id').match(/\d+$/)[0];
        bindCouseChangeEvent(courseTagId, index);
    });
    @endif

    // ラベル追加
    $('.childLabel').on('change', function(event) {
        // イベント停止
        event.preventDefault();
        var addLabelId = $(this).val();
        if(addLabelId == '') {
            return false;
        }
        var data = {
            tags : $('#ProgramTags').val(),
            add_label_id : addLabelId,
        }

        editLabelAjax('add', data);
        return false;
    });

    // もっと見る
    $('.ShowMore').on('click', function(event) {
        // イベント停止
        event.preventDefault();
        var name = $(this).attr('forShowMore');
        if (name) {
            var element = $('.' + name);
            if (element) {
                $(element).show();
            }
        }
        $(this).hide();
        return false;
    });


    // マルチコース選択
    $(document).on('change', '[name=multi_course]', function(event) {
        const checked = event.target.checked;
        const data = { id: getAddLayoutNo(), };

        // TINY MCEの仕様により一度初期化したものは再度初期化できないため、レイアウトを再設定
        if (checked) {
            removeAllPointLayout();

            addPointLayout(data);
            showCourseLayout(data);
        } else {
            removeAllCourseLayout();
            removeAllPointLayout();

            addPointLayout(data);
        }
    });
});

function removeAllCourseLayout() {
    $('.courses').empty();
}

function removeAllPointLayout() {
    $('[id^=pointLayout]').remove();
}

function getAddLayoutNo() {
    const nextLayoutNo = $('#maxLayoutNo').val(Number($('#maxLayoutNo').val()) + 1);
    return Number($(nextLayoutNo).val());
}

function bindSchedule(scheduleTagId, courseNo) {
    tinyMCE.init({
        mode: "textareas",
        selector: `#${scheduleTagId}${courseNo}`,
        language: "ja",
        toolbar: "forecolor link bullist code",
        plugins: "textcolor link lists code",
        fullpage_default_doctype: "",
        fullpage_default_encoding: "UTF-8",
        menubar: false,
        statusbar: false,
        cleanup : false,

        force_br_newlines : true,
        force_p_newlines : false,
        forced_root_block : '',

        document_base_url : "{{ config('app.client_url') }}",
        convert_urls : false
    });
}

function bindCouseChangeEvent(courseTagId, courseNo) {
    $(document).on('change', `#${courseTagId}${courseNo}`, function() {
        var matches = $(this).attr('id').match(/\d+$/);
        if (matches == null || matches == undefined) return;
        var courseName = $(this).val();
        $(`#pointLayout${matches[0]} legend`).text(`ポイント - ${courseName} - `)
    });
}


function showCourseLayout(data)
{
    const url = '{{ route('programs.show_course') }}';

    $.ajax({
        type: 'GET',
        url: url,
        data: data,
        dataType: 'html',
        success: function(res) {
            $('.courses').html(res);
            $('.courses .btn').onclick = function() {
                const data = { id: 1 };
            };
            bindCouseChangeEvent('CourseName', data.id);
        },
        error: function(res) {
            console.error(res);
        }
    });
}

function addCourse()
{
    const data = { id: getAddLayoutNo() };
    addCourseLayout(data);
    addPointLayout(data);
}

function addCourseLayout (data)
{
    const courseUrl = '{{ route('programs.add_course') }}';

    $.ajax({
        type: 'GET',
        url: courseUrl,
        data: data,
        dataType: 'html',
        success: function(res) {
            $('#courseDetail').append(res);
            bindCouseChangeEvent('CourseName', data.id);
        },
        error: function(res) {
            console.error(res);
        }
    });
}

function addPointLayout (data)
{
    const pointUrl = '{{ route('programs.add_point') }}';

    $.ajax({
        type: 'GET',
        url: pointUrl,
        data: data,
        dataType: 'html',
        success: function(res) {
            $('.points').append(res);
            bindSchedule('ProgramScheduleRewardCondition', data.id);
        },
        error: function(res) {
            console.error(res);
        }
    });
}

// コース削除
function removeCourse(event) {
    const courseCnt = $('[id^=AffCourseId]').length;
    if (courseCnt === 1) {
        alert('コースは1つ以上必要です。');
        return false;
    }
    const id = event.parentNode.parentNode.parentNode.id.match(/\d+$/);
    $(`#pointLayout${id}`).remove();
    $(`#courseLayout${id}`).remove();
}
//get num
function getNum(element)
{

    var parentElement = $(element).parent();
    var stockNote = parentElement.length > 0 ? parentElement.find('.stock_note').last() : null;
    var getid = stockNote && stockNote.attr('id') ? stockNote.attr('id').match(/\d$/) : null;
    var digit = getid ? getid[0] : null;
    if (digit){
        var id = parseInt(digit) + 1;
        return id ;
    }

    return 1;
}
//add note stock cv
function addNoteCv(element)
{
    const data = { id: getNum(element) };
    addNoteLayout(data);
}

function addNoteLayout (data)
{
    const courseUrl = '{{ route('programs.add_note_stockcv') }}';

    $.ajax({
        type: 'GET',
        url: courseUrl,
        data: data,
        dataType: 'html',
        success: function(res) {
            $('#listNote').append(res);
            bindCouseChangeEvent('CourseName', data.id);
        },
        error: function(res) {
            console.error(res);
        }
    });
}

// remove note stock cv
function removeNote(event) {
    const id = event.parentNode.parentNode.parentNode.parentNode.id.match(/\d+$/);
    $(`#noteLayout${id}`).remove();
}


//対象デバイス => タイトル
@if (!isset($program['id']))
$(document).on('change', '.display', function(event) {
    event.preventDefault();
    // check status of checkboxes
        var checkedOptions = $('.display:checked');
       if (checkedOptions.length === 1 &&
       (checkedOptions.parent('label').text().trim() == 'Android' || checkedOptions.parent('label').text().trim() == 'iOS')){
        $('#ProgramTitle').val('('+ checkedOptions.parent('label').text().trim()+ ')');
       }
       else
       {
        $('#ProgramTitle').val('');
       }
});

@endif


//change url affiriate when change asp
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
//datetime picker
$( function() {
    $( "#ProgramStartAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
    $( "#ProgramStopAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
    $( "#ProgramReleasedAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
  } );

// format number fee
function truncateToDecimalPlace(num, decimalPlaces) {
    const factor = Math.pow(10, decimalPlaces);
    return Math.floor(num * factor) / factor;
}
// ユーザー報酬/報酬額 : PointRewards
$(document).on('change','#PointFee, #PointRewards', function(event) {
    event.preventDefault();
    // check status of checkboxes
    var currentparent = $(this).closest('.point');
    var feebonus = currentparent.find('#PointRewards');
    var feebonusval = feebonus.val();
    var fee_type = currentparent.find('#PointFeeType');
    var fee = currentparent.find('#PointFee');
    var rate = currentparent.find('#rate');
    var checkpoint = currentparent.find('#SaveStatusPoint');
    if (event.target.id == 'PointRewards' ) {
        var fees = feebonusval * 0.7;
        if (fee_type.val() == 1) {
            fee.val(Math.round(fees));
            rate.html('ユーザー報酬/報酬額：' + '70%');
            checkpoint.val(0.7);
        }
        else{
            fee.val((Math.floor(fees*10))/10);
            rate.html('ユーザー報酬/報酬額：' + '70%');
            checkpoint.val(0.7);
        }
    }
    var feeval = Math.floor(parseFloat(fee.val())*10)/10;
    if ((feebonusval !='' || feebonusval != 0 ) && event.target.id == 'PointFee') {
        rate.html("ユーザー報酬/報酬額：" + (feeval/feebonusval*100).toFixed(1)+"%");
        checkpoint.val(feeval/feebonusval);
    }
    if (feebonusval =='' || feebonusval == 0 ){
        rate.html('');
        checkpoint.val(0);
    }
    if (fee_type.val() == 1 && fee.val() != '') {
        fee.val(Math.round(parseFloat(fee.val())));
    }
    if (fee_type.val() == 2 && fee.val() != '') {
        fee.val(Math.floor(parseFloat(fee.val())*10)/10);
    }
});
//fee type
$(document).on('change','#PointFeeType', function(event) {
    event.preventDefault();
    var fee_type = $(this).val();
    var currentparent = $(this).closest('.point');
    var fee = currentparent.find('#PointFee');
    if (fee_type == 1 && fee.val() != '') {
        fee.val(Math.round(parseFloat(fee.val())));
    }
    if (fee_type == 2 && fee.val() != '') {
        fee.val(Math.floor(parseFloat(fee.val())*10)/10);
    }
});

//alert point when save
var isPopupShown = false;
$(document).on('click','.save-all', function(event) {
    event.preventDefault();
    // check status of checkboxes
    var currentparent = $(this).closest('body');
    var checkpoint = currentparent.find('[id="SaveStatusPoint"]');
    var popupCheckpoint = currentparent.find('#popupCheckpoint').get(0);
    var values = [];
    checkpoint.each(function(index, element) {
        var value = parseFloat($(element).val());
        values.push(value);

      });
    var maxValue = Math.max(...values);
    if (maxValue>1){
        if (isPopupShown) {
            $('#LockFormId').submit();
          } else {
            popupCheckpoint.classList.add('show');
            window.scrollTo(0, 0);
            isPopupShown = true;
            return false;
          }
    }
    else{
        $('#LockFormId').submit();
    }

});


// タグコピー
$(document).on('click', '.tagCopy', function(event) {
    // イベント停止
    event.preventDefault();

    var parent = $(this).parent();
    var editId = parent.attr('forEdit');
    var deleteId = parent.attr('forDelete');

    var tagTextarea = $('#' + editId);
    var str_arr = tagTextarea.val().split(',');
    var v = $(this).text();
    var index = str_arr.indexOf(v);
    if (index > -1) {
        return false;
    }
    str_arr.push(v);
    copyTag(str_arr);
    return false;
});
// タグ削除
$(document).on('click', '.tagDelete', function(event) {
    // イベント停止
    event.preventDefault();

    var parent = $(this).parent();
    var editId = parent.attr('forEdit');
    var copyId = parent.attr('forCopy');

    var tagTextarea = $('#' + editId);
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
    $('#' + editId).val(str_arr.join(','));
    $(this).remove();
    return false;
});
//ラベル削除
$(document).on('click', '.labelDelete', function(event) {
    // イベント停止
    event.preventDefault();
    var removeLabelId = $(this).attr('forLabelId');
    var data = {
        remove_label_id : removeLabelId,
    }
    editLabelAjax('remove', data);
    return false;
});

// セレクトボックス変更
$(document).on('change','.parentLabel', function(event) {
    var subgroup =  $(this).find('option:selected').val();
    var childSelect = $(this).attr('childGroup');
    $('#'+childSelect).find("option").each(function(index, element){
        $('#'+childSelect).removeAttr('disabled');
        var group = $(element).attr('parentLabel');
        if( group ){
            if( subgroup != group ){
                $(element).hide();
            } else {
                $(element).show();
            }
        }
    });
    $('#'+childSelect).val('').change();
});

function editLabelAjax(type, data) {
    var ajaxUrl = '';
    var labelTextarea = $('#ProgramLabels');
    if(type == 'add') {
        var ajaxUrl = "{{ route('programs.add_label_tag') }}";
    } else if(type == 'remove') {
        var ajaxUrl = "{{ route('programs.remove_label_tag') }}";
    }
    data.label_ids = $(labelTextarea).val();

    $.ajax({
        type: 'GET',
        data: data,
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
            var label_list = data['labelList'];
            var new_label_ids = [];
            $('#LabelList').children('a').remove();
            $.each(label_list, function(index, element) {
                var a = $("<a></a>", {href: "#", class: "labelDelete", forLabelId: element['id']});
                new_label_ids.push(element['id']);
                a.text(element['name']);
                $('#LabelList').append(a);
            });
            var label_ids = new_label_ids.join(',');
            $(labelTextarea).val(label_ids);
            // ラベル削除の場合はここまで
            if(type == 'remove') {
                return false;
            }
            // ラベルに紐づくタグの追加
            var tag_list = data['tagList'];
            copyTag(tag_list);
        },
        error:function(xhr, textStatus, errorThrown) {
            $('#LabelsMessage').html('失敗しました' + '<br />' + "XMLHttpRequest : " + xhr.status + ",textStatus : " + textStatus + ",errorThrown : " + errorThrown.message);
        }
    });
    return false;
}
function copyTag(tag_list) {
    $('#TagDelete').empty();
    tag_list = tag_list.filter(function (element, index, self) {
        if (element.trim() === '') { return false; }
        return self.indexOf(element) === index;
    });
    $.each(tag_list, function(index, element) {
        var a = $("<a></a>", {href: "#", class: "tagDelete"});
        a.text(element);
        $('#TagDelete').append(a);
    });
    var tag_list = tag_list.join(',');
    $('#ProgramTags').val(tag_list);
    return false;
}

//add question
//get num
function getNumQuestion(element)
{

    var parentElement = $(element).parent();
    var ques = parentElement.length > 0 ? parentElement.find('.question-group').last() : null;
    var getid = ques && ques.attr('id') ? ques.attr('id').match(/\d$/) : null;
    var digit = getid ? getid[0] : null;
    if (digit){
        var id = parseInt(digit) + 1;
        return id ;
    }

    return 1;
}
function addQuestion(element)
{
    const data = { id: getNumQuestion(element) };
    addQuestionLayout(data,null);
}
function addQuestionLayout (data,max)
{
    const questionUrl = '{{ route('programs.add_question') }}';
    $.ajax({
        type: 'GET',
        url: questionUrl,
        data: {data:data, max: max},
        dataType: 'html',
        success: function(res) {
            $('#questionDetail').append(res);
            bindSchedule('Answer', data.id);
        },
    });
}

// remove
function removeQuestion(event) {
    const id = event.parentNode.parentNode.id.match(/\d+$/);
    $(`#question${id}`).remove();
}
// -->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
@if (isset($program['id']))
<li class="active">{{ Tag::link(route('programs.edit', ['program' => $program['id']]), 'プログラム更新') }}</li>
<li>{{ Tag::link(route('programs.create'), '新規プログラム登録') }}</li>
@else
<li class="active">{{ Tag::link(route('programs.create'), '新規プログラム登録') }}</li>
@endif
@endsection

@section('menu.extra')
@if (!isset($program['id']))
<div class="panel-heading">複写</div>
{{ Tag::formOpen(['url' => route('programs.copy'), 'method' => 'post', 'files' => true, 'id' => 'copyFormId', 'class' => 'copyForm']) }}
@csrf    
    <div class="form-group">
        <label for="refererProgramId">プログラムID</label>
        {{ Tag::formText('refererProgramId', old('refererProgramId', ''), ['class' => 'form-control', 'required' => 'required']) }}
    </div>
    <div class="form-group">
        {{ Tag::formSubmit('複写', ['class' => 'btn btn-default']) }}
    </div>
{{ Tag::formClose() }}
@endif
@endsection

@section('content')

@php
$attachment_select_params = ['img_ids_id' => 'ProgramImgIds'];
if (isset($program['id'])) {
    $attachment_select_params['parent_type'] = 'program';
    $attachment_select_params['parent_id'] = $program['id'];
}
@endphp
@include('elements.attachment_select', $attachment_select_params)

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif
<div id="popupCheckpoint" class="alert alert-danger">
    <div id="popupContent">
        <ul>
            <li>
                <p>「報酬額＜ポイント」になっているポイント情報があります。本当に登録しますか？</p>
            </li>
        </ul>
      </div>
</div>
<!-- プログラム複写 -->
@if (!isset($program['id']))
@endif

{{ Tag::formOpen(['url' => route('programs.store'), 'method' => 'post', 'files' => true, 'id' => 'LockFormId', 'class' => 'LockForm']) }}
@csrf    
{{ Tag::formHidden('maxLayoutNo', old('max_layout_no', 0), ['id' => 'maxLayoutNo']) }}
    {{ Tag::formHidden('img_ids', old('img_ids', $program['img_ids'] ?? ''), ['id' => 'ProgramImgIds']) }}
    <fieldset>
        @if (isset($program['id']))
        <legend>プログラム更新</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $program['id'] }}
            {{ Tag::formHidden('id', old('id', $program['id'] ?? '')) }}
        </div>
        @else
        <legend>プログラム作成</legend>
        @endif
        @php
        asort($asp_map);
        @endphp
        <div class="form-group">
            <label for="AffiriateAspId">ASP</label><br />
            @if (isset($program['id']))
            {{ $asp_map[$affiriate_list->first()->asp_id] ?? '' }}
            @else
            {{ Tag::formSelect('affiriate[asp_id]', ['' => '---'] + $asp_map, old('affiriate.asp_id', $affiriate['asp_id'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'AffiriateAspId']) }}
            @endif
        </div>
        <div class="form-group">
            <label for="AffiriateAdId">ASP別検索ID</label><br />
            @if (isset($program['id']))
            {{ $affiriate_list->first()->ad_id ?? '' }}
            @else
            {{ Tag::formText('affiriate[ad_id]', old('affiriate.ad_id', $affiriate['ad_id'] ?? ''), ['class' => 'form-control', 'id' => 'AffiriateAdId']) }}<br />
            @endif
        </div>
        <p><b>対象デバイス</b></p>
        <div class="checkbox">
            @php
            $device_list = old('device', $program['device']);
            $device_map = config('map.device');
            @endphp
            @foreach($device_map as $key => $label)
            @php
            $check_box_id = 'device'.$key;
            @endphp
            <label for="{{ $check_box_id }}" class="selected">
                {{ Tag::formCheckbox('device['.$key.']', $key, in_array($key, $device_list), ['id' => $check_box_id,'class' => 'display']) }}{{ $label}}
            </label>
            @endforeach
        </div>
    	<div class="form-group">
            <label for="ProgramTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $program['title'] ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'ProgramTitle']) }}<br />
        </div>
        <div class="form-group">
            <label for="ProgramDescription">ディスクリプション</label><br />
            {{ Tag::formTextarea('description', old('description', $program['description'] ?? ''), ['class' => 'form-control', 'rows' => 3, 'id' => 'ProgramDescription']) }}<br />
        </div>
        <div class="form-group">
            @if (isset($program['id']))
                {{ $program['multi_course'] ? 'マルチステップコンバージョン設定済み' : 'マルチステップコンバージョン未設定'}}
                {{ Tag::formHidden('multi_course', $program['multi_course'] ?? '0', ['id' => 'multiCourse']) }}
            @else
                <label for="ProgramMultiCourse">マルチステップコンバージョン</label><br />
                {{ Tag::formCheckbox('multi_course', '1', old('multi_course', $program['multi_course'] ?? '0') == '1', ['id' => 'ProgramMultiCourse']) }}<br />
            @endif
        </div>
        <div class="form-group">
            <label for="ProgramDetail">詳細</label><br />
            {{ Tag::formTextarea('detail', old('detail', $program['detail'] ?? ''), ['class' => 'form-control', 'rows' => 5, 'id' => 'ProgramDetail']) }}<br />
        </div>

        <div class="form-group">
            <label for="AdTitle">広告主タイトル</label><br />
            {{ Tag::formText('ad_title', old('ad_title', $program['ad_title'] ?? ''), ['class' => 'form-control', 'id' => 'AdTitle']) }}<br />
        </div>
        <div class="form-group">
            <label for="AdText">広告主テキスト</label><br />
            {{ Tag::formTextarea('ad_detail', old('ad_detail', $program['ad_detail'] ?? ''), ['class' => 'form-control', 'rows' => 5, 'id' => 'AdDetail']) }}<br />
        </div>
        <div class="form-group">

        <div class="form-group">
            @php
            $maxDispOrder = 1;
                if(isset($question_list) && !$question_list->isEmpty()){
                    $maxDispOrder = collect($question_list)->max('disp_order');
                    $maxDispOrder = $maxDispOrder + 1;
                }
            @endphp
            <label for="Questions" style="margin: 20px 0;">よくある質問</label>
            @if(isset($program['id']))
            {{ Tag::link(route('program_questions.create',['maxDispOrder' => $maxDispOrder ,'program' => $program['id']]), '追加', ['class' => 'btn btn-small btn-info']) }}<br />
            @else
            <a style="margin: 10px;" id="addQuestion" class="btn btn-small btn-info" href="javascript:void(0);" onclick="addQuestion(this)">
                追加
            </a>
            @endif
            <div id="questionDetail">
                @php
                if (!isset($progam['id']))
                {
                    if (old('questions')) $questions = old('questions');
                }
                @endphp
                @if(isset($questions))
                @include('elements.programs_question_layout', [$questions])
                @endif

            </div>
            @if(isset($question_list) && !$question_list->isEmpty() )
                @php
                $question_list = $question_list->sortBy('disp_order');
                @endphp
            <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
                <tr>
                    <th style="width: 10%;">操作</th>
                    <th style="width: 80%;">質問</th>
                    <th style="width: 10%;">表示順</th>
                </tr>
                <tbody>
                    @foreach ($question_list as $question)
                    <tr{!! $loop->iteration > 5 ? ' style="display:none" class="QuestionMore "' : ' class="QuestionShow"'!!}>
                        <td class="actions" style="white-space:nowrap;">
                            {{ Tag::link(route('program_questions.edit',['program_question' => $question]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
                        </td>
                        <td>
                            {{ $question['question'] }}
                        </td>

                        <td>
                            {{ $question['disp_order'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($question_list->count() > 5)
                <div class="form-group">
                    <a href="#" class="btn btn-small btn-info ShowMore" forShowMore="QuestionMore">もっと見る</a>
                </div>
                @endif
            @endif
        </div>
        <p><b>対象キャリア</b></p>
        <div class="checkbox">
            @php
            $carrier_list = old('carrier', $program['carrier']);
            $carrier_map = config('map.carrier');
            @endphp
            @foreach($carrier_map as $key => $label)
            @php
            $check_box_id = 'carrier'.$key;
            @endphp
            <label for="{{ $check_box_id }}" class="selected">
                {{ Tag::formCheckbox('carrier['.$key.']', $key, in_array($key, $carrier_list, true), ['id' => $check_box_id]) }}{{ $label }}
            </label>
            @endforeach
        </div>
        <p><b>ショップカテゴリ</b></p>
        <div class="checkbox">
            @php
            $shop_category_list = old('shop_category', $program['shop_category']);
            $shop_category_map = config('map.shop_category');
            @endphp
            @foreach($shop_category_map as $key => $label)
            @php
            $check_box_id = 'shop_category'.$key;
            @endphp
            <label for="{{ $check_box_id }}" class="selected">
                {{ Tag::formCheckbox('shop_category['.$key.']', $key, in_array($key, $shop_category_list, true), ['id' => $check_box_id]) }}{{ $label }}
            </label>
            @endforeach
        </div>
        <p><b>ラベル</b></p>
        @php
        $p_labels = old('labels', $program['labels'] ?? '');
        $label_id_list = explode(',', $p_labels);
        $label_list = !empty($label_id_list) ? \App\Label::select('id','name', 'label_id')->whereIn('id', $label_id_list)->get() : [];
        @endphp
        <div id="LabelList" class="well">
            @foreach ($label_list as $label)
            {{ Tag::link('#', $label->name, ['class' => 'labelDelete', 'forLabelId' => $label->id]) }}
            @endforeach
        </div>
        {{ Tag::formHidden('labels', $p_labels, ['id' => 'ProgramLabels']) }}
        @foreach (config('map.label_type') as $key => $type)
        @if($key!=\App\Label::TYPE_ENTRY_MAX)
        <div class="form-group">
            <label>{{ $type }}</label><br />
            @foreach ($label_data_map[$key] as $level => $label_list)
            @php
            $class = '';
            $optionsAttributes = ['id' => 'Label'.$key];
            $attributes = [];
            foreach($label_list as $id => $label) {
                $parent_id = $label_options_attributes[$id] ?? 0;
                $optionsAttributes[$id] = ['parentLabel' => $parent_id];
            }
            $id = 'label'.$key.$level;
            if($loop->last) {
                // 子ラベル
                $class = 'childLabel';
                $attributes = ['id' => $id, 'class' => $class];
            } else {
                // 親ラベル
                $disabled = $loop->first ? false : true;
                $class = 'parentLabel';
                $childGroup = 'label'.$key.($level+1);
                $attributes = ['id'  => $id, 'childGroup' => $childGroup, 'class' => $class, 'disabled' => $disabled];
            }
            @endphp
            {{ Tag::formSelect($id, ['' => '---'] + $label_list, null, $attributes, $optionsAttributes) }}
            @endforeach
        </div>
        @endif
        @endforeach
        <p><b>タグ</b></p>
        <div id="TagDelete" class="well" forEdit="ProgramTags" forCopy="TagCopy">
            @php
            $ptags = old('tags', $program['tags'] ?? '');
            $tag_list = explode(',', $ptags);
            @endphp
            @foreach ($tag_list as $tag)
            {{ Tag::link('#', $tag, ['class' => 'tagDelete']) }}
            @endforeach
        </div>
        {{ Tag::formHidden('tags', $ptags, ['id' => 'ProgramTags']) }}
        <p><b>タグ候補</b></p>
        <div id="TagCopy" class="well" forEdit="ProgramTags" forDelete="TagDelete">
            @foreach ($high_use_tag_list as $tag)
            {{ Tag::link('#', $tag, ['class' => 'tagCopy']) }}
            @endforeach
        </div>

        <div class="form-group"><div class="form-inline">
            <div class="form-group">
                <label for="ProgramMultiJoin">複数参加</label><br />
                {{ Tag::formSelect('multi_join', ['' => '---'] + config('map.multi_join'), old('multi_join', $program['multi_join'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'ProgramMultiJoin']) }}<br />
            </div>
            <div class="form-group">
                <label for="ProgramTest">テスト</label><br />
                {{ Tag::formSelect('test', config('map.test'), old('test', $program['test'] ?? ''), ['class' => 'form-control', 'id' => 'ProgramTest']) }}<br />
            </div>
            <div class="form-group">
                <label for="ProgramListShow">リスト公開</label><br />
                {{ Tag::formSelect('list_show', config('map.enable'), old('list_show', $program['list_show'] ?? ''), ['class' => 'form-control', 'id' => 'ProgramListShow']) }}<br />
            </div>
        </div></div>

        <div class="form-group">
            <label for="ProgramFeeCondition">成果条件</label><br />
            {{ Tag::formText('fee_condition', old('fee_condition', $program['fee_condition'] ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'ProgramFeeCondition']) }}<br />
        </div>
        <div class="form-group">
            <label for="ProgramPriority">ウェイト</label><br />
            {{ Tag::formNumber('priority', old('priority', $program['priority']), ['class' => 'form-control', 'min' => '1', 'max' => '999','required' => 'required', 'id' => 'ProgramPriority']) }}<br />
        </div>

        {{-- <div class="form-group">
            <label for="ProgramRecipeIds">レシピID</label><br />
            {{ Tag::formTextarea('recipe_ids', old('recipe_ids', $program['recipe_ids'] ?? ''), ['class' => 'form-control', 'rows' => 1, 'id' => 'ProgramRecipeIds']) }}<br />
        </div> --}}

        <div class="form-group">
            <label for="ProgramStartAt">掲載期間</label>
            <div class="form-inline">
                {{ Tag::formText('start_at_date', old('start_at_date', $program['start_at_date'] ?? ''), ['class' => 'form-control', 'id' => 'ProgramStartAt']) }}
                {{ Tag::formTime('start_at_time', old('start_at_time', $program['start_at_time'] ?? ''), ['class' => 'form-control']) }}
                ～
                {{ Tag::formText('stop_at_date', old('stop_at_date', $program['stop_at_date'] ?? ''), ['class' => 'form-control', 'id' => 'ProgramStopAt']) }}
                {{ Tag::formTime('stop_at_time', old('stop_at_time', $program['stop_at_time'] ?? ''), ['class' => 'form-control']) }}
            </div>
        </div>

        @if (isset($program['id']))
        <div class="form-group">
            <label for="ProgramReleasedAt">公開日時</label>
            <div class="form-inline">
                {{ Tag::formText('released_at_date', old('released_at_date', $program['released_at_date'] ?? ''), ['class' => 'form-control', 'id' => 'ProgramReleasedAt']) }}
                {{ Tag::formTime('released_at_time', old('released_at_time', $program['released_at_time'] ?? ''), ['class' => 'form-control']) }}
            </div>
        </div>
        @endif
        <div class="form-group stock_cv">
            <label for="ProgramStockCv">在庫CV数</label>
            {{ Tag::formNumber('stock_cv', old('stock_cv', $program['stock_cv'] ?? ''), ['class' => 'form-control col-sm-4', 'id' => 'ProgramStockCv']) }}
            {{ Tag::formHidden('old_stock_cv', $program['stock_cv'] ?? '', ['id' => 'ProgramOldStockCv']) }}
        </div>
        <div class="form-group">
            <label for="StockCvNote">在庫CV更新履歴メモ</label>
            <a id="addNoteCv" class="btn btn-small btn-info" href="javascript:void(0);" onclick="addNoteCv(this)">
                追加
            </a>
            <div id="listNote">
                @php
                    $note_list = old('note') ?? [];
                @endphp
                @include('elements.programs_stockcv_note_layout', [$note_list])
                @if(empty($note_list) && !isset($program['note']))
                    <div class="form-group stock_note" id="noteLayout1">
                        <div class="form-inline">
                            <div class="form-group">
                                <label for="noteId1">メモ</label>
                                <div class="form-group">
                                    <a class="btn btn-danger btn-small removenode"  hrerf="javascript:void(0);" onclick="removeNote(this)">削除</a><br>
                                </div>
                            </div>
                            <br>
                        </div>
                        {{ Tag::formTextarea('note[1]', old('note',''), ['class' => 'form-control','rows' => 3, 'id' => 'note1' ]) }}
                    </div>
                @endif
                @if (isset($program['note']))
                    @foreach ($program['note'] as $key => $value)
                    <div class="form-group stock_note" id="noteLayout{{ $key +1  }}">
                        <div class="form-inline">
                            <div class="form-group">
                                <label for="noteId">メモ{{ ($key +1 ) }}</label>
                                <div class="form-group">
                                    <a class="btn btn-danger btn-small removenode"  hrerf="javascript:void(0);" onclick="removeNote(this)">削除</a><br>
                                </div>
                            </div>
                            <br>
                        </div>
                    {{ Tag::formTextarea('note['.($key +1 ).']', old('note',$value?? ''), ['class' => 'form-control','rows' => 3, 'id' => 'note'.($key+1) ]) }}
                    </div>
                    @endforeach

                @endif
            </div>
        </div>

        <br>
        @if(isset($program['id']))
            <div class="form-group save">
                <label for="alert" class="alert save">コース・ポイント・アフィリエイトの編集用別画面に遷移すると、ここまでに入力された内容が保存されません。<br>
                    一度保存して、編集内容の消失を避けましょう。
                </label>
                <div class="form-group">{{ Tag::formSubmit('保存する', ['class' => 'btn btn-default btn-lg submit']) }}</div>
            </div>
        @endif
        <br>
        <div class="form-group courses">
        @php
        if (!isset($progam['id']))
        {
            if (old('course')) $course_list = old('course');
        }
        @endphp

        @if (isset($course_list))
            @if (isset($program['id']))
                @php
                $course_status_map = [
                    0 => ['class' => '', 'status' => '公開中'],
                    1 => ['class' => 'danger', 'status' => '非公開']
                ];
                $course_list = $course_list->sortBy('priority');
                @endphp
                <legend>
                    <a id="course"></a>コース
                    {{ Tag::link(route('courses.create', ['program' => $program['id']]), '追加', ['class' => 'btn btn-small btn-info']) }}
                </legend>
                <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
                    <tr>
                        <th>操作</th>
                        <th>公開状態</th>
                        <th>連携コースID</th>
                        <th>コース名</th>
                        <th>表示順</th>

                    </tr>
                    @foreach ($course_list as $course)
                    <tr{!! $loop->iteration > 5 ? ' style="display:none" class="CourseMore "' . $course_status_map[$course->status]['class'] . '"' : ' class="' . $course_status_map[$course->status]['class'] . '"' !!}>
                        <td class="actions" style="white-space:nowrap">
                            {{ Tag::link(route('courses.edit', ['course' => $course]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
                        </td>
                        <td>{{ $course_status_map[$course->status]['status'] }}</td>
                        <td>{{ $course->aff_course_id }}</td>
                        <td>{{ $course->course_name }}</td>
                        <td>{{ $course->priority }}</td>
                    </tr>
                    @endforeach
                </table>
                @if ($course_list->count() > 5)
                <div class="form-group">
                    <a href="#" class="btn btn-small btn-info ShowMore" forShowMore="CourseMore">もっと見る</a>
                </div>
                @endif
            @else
                @include('elements.programs_course_list', [$program, $course_list])
            @endif
        @endif
        </div>
        <br>

        <!-- ポイントレイアウト読み込み -->
        @php
            $point_list = old('point') ?? $point_list;
        @endphp
        <div class="points">
        @foreach ($point_list as $course_no => $point)
        @php
            $course = old('course') ?? [];
        @endphp
        @include('elements.programs_point_layout', [$program, $course_no, $course, $point])
        @endforeach
        </div>

        <div class="form-group">
            <label for="ProgramMemo">Memo</label>
            {{ Tag::formTextarea('memo', old('memo', $program['memo'] ?? ''), ['class' => 'form-control','rows' => 3, 'id' => 'ProgramMemo']) }}<br />
        </div>

        <hr />

        <legend>
            <a id="affiliate"></a>アフィリエイト
            @if (isset($program['id']))
            {{ Tag::link(route('affiriates.create', ['program' => $program['id']]), '追加', ['class' => 'btn btn-small btn-info']) }}
            @endif
        </legend>

        @if (isset($program['id']))
        <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
            <tr>
                <th rowspan="2">操作</th>
                <th>掲載期間</th>
                <th>ASP</th>
                <th>データ連携ID</th>
                <th>ASP別検索ID</th>
                <th>画像</th>
                <th>獲得時期目安</th>
                <th>予定反映目安</th>
            </tr>
            <tr><th colspan="7">URL</th></tr>
            @foreach ($affiriate_list as $affiriate)
            <tbody{!! $loop->iteration > 5 ? ' style="display:none" class="AffiriateMore"' : '' !!}>
                <tr>
                    <td rowspan="2" class="actions" style="white-space:nowrap">
                        {{ Tag::link(route('affiriates.edit', ['affiriate' => $affiriate]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
                    </td>
                    <td>
                        {{ $affiriate->start_at->format('Y-m-d H:i') }}～
                        {{ $affiriate->stop_at->eq(\Carbon\Carbon::parse('9999-12-31 23:59:59')) ? '' : $affiriate->stop_at->format('Y-m-d H:i') }}
                    </td>
                    <td>{{ isset($affiriate->asp_id) ? $asp_map[$affiriate->asp_id] : '' }}&nbsp;</td>
                    <td>{{ $affiriate->asp_affiriate_id }}&nbsp;</td>
                    <td>{{ $affiriate->ad_id }}&nbsp;</td>
                    <td>{{ Tag::image($affiriate->img_url, 'img', ['width' => '120px']) }}&nbsp;</td>
                    <td>{{ config('map.accept_days')[$affiriate->accept_days] ?? '' }}</td>
                    <td>{{ number_format($affiriate->give_days) }}日</td>
                </tr>
                <tr><td colspan="7">{!! htmlspecialchars($affiriate->url ?? '', ENT_QUOTES, 'UTF-8', true) !!}&nbsp;</td></tr>
            </tbody>
            @endforeach
        </table>
        @if ($affiriate_list->count() > 5)
        <div class="form-group">
            <a href="#" class="btn btn-small btn-info ShowMore" forShowMore="AffiriateMore">もっと見る</a>
        </div>
        @endif
        @else

        <div class="form-group">
            <label for="AffiriateAspAffiriateId">データ連携ID</label><br />
            {{ Tag::formText('affiriate[asp_affiriate_id]', old('affiriate.asp_affiriate_id', $affiriate['asp_affiriate_id'] ?? ''), ['class' => 'form-control', 'id' => 'AffiriateAspAffiriateId']) }}<br />
        </div>
        <div class="form-group">
            <label for="AffiriateUrl">遷移先URL</label><br />
            {{ Tag::formText('affiriate[url]', old('affiriate.url', $affiriate['url'] ?? ''), ['class' => 'form-control', 'id' => 'AffiriateUrl']) }}<br />
            <span id="ajaxAffiriateUrl">ユーザーID:{{ App\Affiriate::COLLEEE_USERID_REPLACE }}<br />RID:{{ App\Affiriate::COLLEEE_RID_REPLACE }}<br /></span>
        </div>
        <div class="form-group">
            <label for="AffiriateImgUrl">画像URL</label><br />
            @php
            $img_id = 'AffiriateImg';
            $affiriate_img_url = old('affiriate.img_url', $affiriate['img_url'] ?? null);
            @endphp
            @if (!isset($affiriate_img_url))
            <img id="{{ $img_id }}" alt="img" width="120px" style="display:none" />
            @else
            {{ Tag::image($affiriate_img_url, 'img', ['id' => $img_id, 'width' => '120px']) }}
            @endif
            {{ Tag::formText('affiriate[img_url]', $affiriate_img_url, ['class' => 'form-control fileUrl', 'maxlength' => '256', 'id' => 'AffiriateImgUrl', 'forImg' => $img_id]) }}<br />
            <input type="button" onclick="openImageDialog('{{ $img_id }}', 'AffiriateImgUrl');" value="参照" /><br />
        </div>
        <div class="form-group">
            <label for="AffiriateGiveDays">予定反映目安</label><br />
            {{ Tag::formNumber('affiriate[give_days]', old('affiriate.give_days', $affiriate['give_days'] ?? null), ['class' => 'form-control', 'id' => 'AffiriateGiveDays']) }}<br />
        </div>
        <div class="form-group">
            <label for="AffiriateAcceptDays">獲得時期目安</label><br />
            <div class="form-inline">
                {{ Tag::formCheckbox('affiriate[accept_speedy]', 1, 0) }}即時
                {{ Tag::formSelect('affiriate[accept_days]', ['' => '---'] + config('map.accept_days'), old('affiriate.accept_days', $affiriate['accept_days'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'AffiriateAcceptDays']) }}
            </div>
        </div>

        <div class="form-group">
            <label for="AffiriateMemo">Memo</label>
            {{ Tag::formTextarea('affiriate[memo]', old('affiriate.memo', $affiriate['memo'] ?? null), ['class' => 'form-control', 'rows' => 3, 'id' => 'AffiriateMemo']) }}<br />
        </div>
        @endif

        <div class="form-group">{{ Tag::formSubmit('保存する', ['class' => 'btn btn-default btn-lg save-all']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
