@extends('layouts.master')

@section('title', 'ポイント管理')

@section('head.load')
<script type="text/javascript">
<!--
$(function(){
    tinyMCE.init({
        mode: "textareas",
        selector:"#ProgramScheduleRewardCondition",
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
});

//datetime picker
$( function() {
    $( "#PointStartAtDate" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
    $( "#PointSaleStopAtDate" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
    $( "#ProgramScheduleDate" ).datepicker({
        dateFormat: "yy-mm-dd"
    });

  } );

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
<div id="popupCheckpoint" class="alert alert-danger">
    <div id="popupContent">
        <ul>
            <li>
                <p>「報酬額＜ポイント」になっているポイント情報があります。本当に登録しますか？</p>
            </li>
        </ul>
      </div>
</div>

<h2>プログラム情報</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>ID</th><th>タイトル</th></tr>
    <tr><td>{{ $program->id }}</td><td>{{ $program->title }}</td></tr>
</table>

@if (isset($course))
<h2>コース情報 </h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr><th>連携コースID</th><th>コース名</th></tr>
    <tr><td>{{ $course->aff_course_id }}</td><td>{{ $course->course_name }}</td></tr>
</table>
@endif

@php
$target_map = config('map.target');
@endphp
{{ Tag::formOpen(['url' => route('points.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($point['id']))
        <legend>ポイント更新</legend>
        {{ Tag::formHidden('id', old('id', $point['id'] ?? '')) }}
        @else
        <legend>ポイント作成</legend>
        {{ Tag::formHidden('program_id', $program->id) }}
        @if (isset($course))
        {{ Tag::formHidden('course_id', $course->id) }}
        @endif
        @endif
        <div class="form-group"><div class="form-inline point">
            <div class="form-group">
                <label for="PointFeeType">成果タイプ</label><br />
                @php
                $fee_type_map = config('map.fee_type');
                $fee_type = old('fee_type', $point['fee_type']);
                @endphp
                @if ($point['editable'])
                {{ Tag::formSelect('fee_type', $fee_type_map, $fee_type ?? '', ['class' => 'form-control', 'required' => 'required', 'id' => 'PointFeeType']) }}<br />
                @else
                {{ $fee_type_map[$fee_type] }}<br />
                @endif
            </div>
            <div class="form-group">
                <label for="PointRewards">報酬額</label><br />
                @php
                $p_reward = old('rewards', $point['rewards'] ?? null);
                @endphp
                @if ($point['editable'])
                {{ Tag::formText('rewards', $p_reward, ['class' => 'form-control', 'required' => 'required', 'id' => 'PointRewards']) }}<br />
                @else
                @if ($point['fee_type'] == 2)
                {{ $p_reward }}%<br />
                @else
                {{ number_format($p_reward) }}P<br />
                @endif
                @endif
            </div>
            <div class="form-group">
                <label for="PointFee">ユーザー報酬</label><br />
                @php
                $p_fee = old('fee', $point['fee'] ?? null);
                @endphp
                @if ($point['editable'])
                {{ Tag::formText('fee', $p_fee, ['class' => 'form-control', 'required' => 'required', 'id' => 'PointFee']) }}<br />
                @else
                @if ($point['fee_type'] == 2)
                {{ $p_fee }}%<br />
                @else
                {{ number_format($p_fee) }}P<br />
                @endif
                @endif
            </div>
            <div class="form-group">
                <label for="PointBonus">ボーナス</label><br />
                @php
                $p_bonus = old('bonus', $point['bonus'] ?? '');
                @endphp
                @if ($point['editable'])
                {{ Tag::formSelect('bonus', ['' => '---'] + $target_map, $p_bonus ?? '', ['class' => 'form-control', 'required' => 'required', 'id' => 'PointBonus']) }}<br />
                @else
                {{ $target_map[$p_bonus] }}<br />
                @endif
            </div>
            <div class="form-group">
                <label for="PointAllBack">100%還元</label><br />
                @if ($point['stopped'])
                {{ $target_map[$point['all_back']] }}<br />
                @else
                {{ Tag::formSelect('all_back', $target_map, old('all_back', $point['all_back'] ?? 0), ['class' => 'form-control', 'id' => 'PointAllBack']) }}<br />
                @endif
            </div>
            <div class="form-group">
                <label for="PointTimeSale">タイムセール</label><br />
                @if ($point['time_sale_editable'])
                {{ Tag::formSelect('time_sale', $target_map, old('time_sale', $point['time_sale'] ?? 0), ['class' => 'form-control', 'id' => 'PointTimeSale']) }}<br />
                @else
                {{ $target_map[$point['time_sale']] }}<br />
                @endif
            </div>
            <div class="form-group">
                <label for="PointTodayOnly">本日限定</label><br />
                @if ($point['stopped'])
                {{ $target_map[$point['today_only']] }}<br />
                @else
                {{ Tag::formSelect('today_only', $target_map, old('today_only', $point['today_only'] ?? 0), ['class' => 'form-control', 'id' => 'PointTodayOnly']) }}<br />
                @endif
            </div>
            <label class="form-group" id="rate"></label>
            <div class="form-group">
                {{ Tag::formHidden('statuspoint' ,0 ,['id' => 'SaveStatusPoint']) }}
            </div>
        </div></div>

        <div class="form-group">
            <label for="PointStartAt">実施期間</label>
            <div class="form-inline">
                @php
                $start_at_date = old('start_at_date', $point['start_at_date'] ?? '');
                $start_at_time = old('start_at_time', $point['start_at_time'] ?? '');
                @endphp
                @if ($point['start_at_editable'])
                {{ Tag::formText('start_at_date', $start_at_date, ['class' => 'form-control', 'id' => 'PointStartAtDate']) }}
                {{ Tag::formTime('start_at_time', $start_at_time, ['class' => 'form-control']) }}
                @else
                {{ $start_at_date .'  '. $start_at_time }}
                @endif
                ～
                @if ($point['stop_at'] != '9999-12-31 23:59')
                {{ $point['stop_at_date'] .' '. $point['stop_at_time']  }}
                @if (isset($point['sale_stop_at']))
                (
                @if ($point['stopped'])
                {{ $point['sale_stop_at_date'] .' '. $point['sale_stop_at_time'] }}
                @elseif (isset($point['sale_stop_at']))
                {{ Tag::formText('sale_stop_at_date', $point['sale_stop_at_date'], ['class' => 'form-control', 'id' => 'PointSaleStopAtDate']) }}
                {{ Tag::formTime('sale_stop_at_time', $point['sale_stop_at_time'], ['class' => 'form-control']) }}
                @endif
                )
                @endif
                @endif
            </div>
        </div>

        <div class="form-group"><table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
            <tr>
                <th>獲得条件</th>
                <th>メモ</th>
                <th>掲載期間</th>
                <th>最終更新者</th>
                <th>更新日時</th>
            </tr>
            @foreach ($program_schedule_list as $p_program_schedule)
            <tr>
                <td>{!! $p_program_schedule->reward_condition !!}</td>
                <td>{!! nl2br(e($p_program_schedule->memo)) !!}</td>
                <td>
                    {{ $p_program_schedule->start_at->format('Y-m-d H:i') }}～
                    @if (!$p_program_schedule->stop_at->eq(\Carbon\Carbon::parse('9999-12-31 23:59:59')))
                    {{ $p_program_schedule->stop_at->format('Y-m-d H:i') }}
                    @endif
                </td>
                <td>{{ $p_program_schedule->admin->email ?? '' }}</td>
                <td>{{ $p_program_schedule->updated_at ? $p_program_schedule->updated_at->format('Y-m-d H:i') : '' }}</td>
            </tr>
            @endforeach
            @if (isset($program_schedule))
            @php 
            $edit = old('program_schedule.edit', isset($program_schedule['id']) ? 1 : 0);
            @endphp
            <tbody id="ProgramScheduleForm1" {!! $edit ? 'style="display:none"' : '' !!}><tr>
                <td colspan="5"><label for="ProgramScheduleEdit" class="selected">
                    {{ Tag::formCheckbox('program_schedule[edit]', 1, false, ['id' => 'ProgramScheduleEdit']) }}更新
                </label></td>
            </tr></tbody>
            <tbody id="ProgramScheduleForm2" {!! $edit ? '' : 'style="display:none"' !!}><tr {!! isset($program_schedule['id']) ? 'class="warning"' : '' !!}>
                <td>
                    @if(isset($program_schedule['id']))
                    {{ Tag::formHidden('program_schedule[edit]', 1) }}
                    {{ Tag::formHidden('program_schedule[id]', $program_schedule['id'], ['id' => 'ProgramScheduleId']) }}
                    @endif
                    {{ Tag::formTextarea('program_schedule[reward_condition]', old('program_schedule.reward_condition', $program_schedule['reward_condition'] ?? null), ['class' => 'form-control', 'rows' => 10, 'id' => 'ProgramScheduleRewardCondition']) }}
                </td>
                <td>{{ Tag::formTextarea('program_schedule[memo]', old('program_schedule.memo', $program_schedule['memo'] ?? null), ['class' => 'form-control', 'rows' => 3]) }}</td>
                <td>
                    {{ Tag::formText('program_schedule[start_at_date]', old('program_schedule.start_at_date', $program_schedule['start_at_date'] ?? null), ['class' => 'form-control', 'id' => 'ProgramScheduleDate']) }}
                    {{ Tag::formTime('program_schedule[start_at_time]', old('program_schedule.start_at_time', $program_schedule['start_at_time'] ?? null), ['class' => 'form-control']) }}
                </td>
                <td>{{ isset($program_schedule['admin']->email) ? $program_schedule['admin']->email : '' }}</td>
                <td>{{ isset($program_schedule['updated_at']) ? $program_schedule['updated_at']->format('Y-m-d H:i') : '' }}</td>
            </tr></tbody>
            @endif
        </table></div>
        @if (!$point['stopped'])
        <div class="form-group">
            {{ Tag::formSubmit('送信', ['class' => 'btn btn-default save-all', 'style' => 'margin-right: 20px; border-radius: 4px;']) }}
        </div>
        @endif
    </fieldset>
{{ Tag::formClose() }}

@endsection
