@extends('layouts.master')
@section('title', 'コース管理')
@section('head.load')
<script type="text/javascript">
<!--
$(function(){
    tinyMCE.init({
        mode: "textareas",
        selector:"#ProgramScheduleRewardCondition0",
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
        height: 250,
        document_base_url : "{{ config('app.client_url') }}",
        convert_urls : false
    });
    $('#ProgramScheduleEdit').on('change', function() {
        $('#ProgramScheduleForm1').hide();
        $('#ProgramScheduleForm2').show();
    });
    $(document).on('change', `#CourseName`, function() {
        var courseName = $(this).val();
        $(`#pointLayout0 legend`).text(`ポイント - ${courseName} -`)
    });

    @if (isset($course['id']))
    @if ($course->status == 0)
    $('#destoryBtn').on('click', function() {
        const result = confirm({!! '"このコースを非公開にしますか?:' . $course->course_name . '?"' !!} );
        if (result) {
            $('#destoryForm').submit();
        }
    });
    @elseif ($course->status == 1)
    $('#enableBtn').on('click', function() {
        const result = confirm({!! '"このコースを公開にしますか?:' . $course->course_name . '?"' !!} );
        if (result) {
            $('#enableForm').submit();
        }
    });
    @endif
    @endif
});
//reward/fee
$(document).on('change','#PointFee, #PointRewards', function(event) {
    event.preventDefault();
    // check status of checkboxes
    var currentparent = $(this).closest('.point');
    var feebonus = currentparent.find('#PointRewards');
    var feebonusval = feebonus.val();
    var fee = currentparent.find('#PointFee');
    var fee_type = currentparent.find('#PointFeeType');
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

//fee_type
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
            $('.LockForm').submit();
          } else {
            popupCheckpoint.classList.add('show');
            window.scrollTo(0, 0);
            isPopupShown = true;
            return false;
          }

    }
    else{
        $('.LockForm').submit();
    }

});
// -->
</script>
@endsection
@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
@if (isset($program->id))
<li>{{ Tag::link(route('programs.edit', ['program' => $program]), 'プログラム更新') }}</li>
@endif
@endsection
@section('content')
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
@if (isset($program->id))
<h2>プログラム情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>タイトル</th></tr>
    <tr><td>{{ $program->id }}</td><td>{{ $program->title }}</td></tr>
</table>
@endif
@php
$target_map = config('map.target');
@endphp
{{ Tag::formOpen(['url' => route('courses.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
@if (isset($course->id))
    <legend>コース更新</legend>
    {{ Tag::formHidden('id', old('id', $course->id ?? '')) }}
    @else
    <legend>コース作成</legend>
    {{ Tag::formHidden('program_id', $program->id) }}
    @endif
    <fieldset>
        <div class="form-group">
            <label for="PointFeeType">連携コースID</label><br />
            {{ Tag::formText('aff_course_id', old('aff_course_id', $course->aff_course_id ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'id' => 'AffCourseId']) }}<br />
        </div>
        <div class="form-group">
            <label for="PointFeeType">コース名</label><br />
            {{ Tag::formText('course_name', old('course_name', $course->course_name ?? ''), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'CourseName']) }}<br />
        </div>
        <div class="form-group">
            <label for="PointFeeType">表示順</label><br />
            {{ Tag::formNumber('priority', old('priority', $course->priority ?? ''), ['class' => 'form-control', 'id' => 'Priority']) }}<br />
        </div>

        <!-- ポイントレイアウト読み込み -->
        @if (!isset($course->id))
        @php
            $point_list = old('point') ?? $point_list;
        @endphp
        @foreach ($point_list as $course_no => $point)
        <div class="points">
        @php
            $course = old('course') ?? [];
        @endphp
        @include('elements.programs_point_layout', [$program, $course_no, $course, $point])
        </div>
        @endforeach
        @endif

        <div class="form-group"><div class="form-inline">
            {{ Tag::formSubmit('送信', ['class' => 'btn btn-default save-all', 'style' => 'margin-right: 20px; border-radius: 4px;']) }}
            @if (isset($course->id))
            @if ($course->status == 0)
            {{ Tag::formButton('非公開', ['id' => 'destoryBtn', 'class' => 'btn btn-small btn-danger', 'style' => 'margin-right: 20px; border-radius: 4px;',]) }}
            @elseif ($course->status == 1)
            {{ Tag::formButton('公開', ['id' => 'enableBtn', 'class' => 'btn btn-small btn-info', 'style' => 'margin-right: 20px; border-radius: 4px;',]) }}
            @endif
            @endif
        </div></div>
    </fieldset>
    {{ Tag::formClose() }}

    @if (isset($course->id))
    @if ($course->status == 0)
    {{ Tag::formOpen(['url' => route('courses.destroy', ['course' => $course]), 'id' => 'destoryForm']) }}
    @csrf
    @method('DELETE')
    {{ Tag::formClose() }}
    @elseif ($course->status == 1)
    {{ Tag::formOpen(['method' => 'POST', 'url' => route('courses.enable', ['course' => $course]), 'id' => 'enableForm']) }}
    @csrf
    {{ Tag::formClose() }}
    @endif
    @endif

@endsection
