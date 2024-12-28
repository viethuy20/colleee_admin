@extends('layouts.master')

@section('title', 'おすすめ広告登録')

@section('head.load')

<script type="text/javascript">
<!--

//datetime picker
$( function() {
    $( "#EntriesStartAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });
    $( "#EntriesStopAt" ).datepicker({
        dateFormat: "yy-mm-dd"
    });

  } );
  $(document).ready(function() {    
    $('#ProgramId').on('input', function() {    
        let element = $(this);
        var id = element.val();
        if(id !== '') {
                
            
            $.ajax({
            type: 'GET',
            url: "{{ route('recommend_program.program') }}",
            data: {
                id :id
            },
            dataType: 'json',
            success: function(res) {
                $('#Title').val(res.program_name);
            },
            error: function(res) {
                console.log(res);
            }
            });
        }
    
    });    
});   

// -->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('recommend_program.index'), 'おすすめ広告一覧') }}</li>
<li class="active">{{ Tag::link(route('recommend_program.create'), 'おすすめ広告追加') }}</li>
@endsection

@section('menu.extra')
@endsection
@section('content')

@if (count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif
@php
if(isset($recommend_program->id)){
    $start_at = $recommend_program['start_at'];
    $date_time = new DateTime($start_at);
    $start_at_date = $date_time->format('Y-m-d');
    $start_at_time = $date_time->format('H:i');
    $stop_at = $recommend_program['stop_at'];
    $date_time = new DateTime($stop_at);
    $stop_at_date = $date_time->format('Y-m-d');
    $stop_at_time = $date_time->format('H:i');
}

@endphp
<!-- プログラム複写 -->

<form action="{{ route('recommend_program.store') }}" method="post" enctype="multipart/form-data" id="LockFormId" class="LockForm">
    @csrf
    <fieldset>
    <legend>
        @if (isset($recommend_program->id))
        <input type="hidden" name="id" value="{{ $recommend_program->id }}">        
        @endif
        おすすめ広告登録
    </legend>

        <div id="entries_detail">
                <div class="entries-group">
                    <div class="form-group">
                        <label for="entriesTitle">プログラムID</label>
                        <input type="text" name="program_id" required value="{{ old('program_id', $recommend_program['program_id'] ?? '') }}" class="form-control" required id="ProgramId">
                    </div>
                    <div class="form-group">
                        <label for="entriesTitle">タイトル</label>
                        <input type="text" name="title" required value="{{ old('title', $recommend_program['title'] ?? '') }}" class="form-control" required id="Title">
                    </div>
                    <div class="form-group">
                        <label for="ContentDevices">対象デバイス</label><br />
                        {{ Tag::formSelect('device_type', config('map.device2'), old('device_type', $recommend_program['device_type']), ['class' => 'form-control', 'id' => 'ContentDevices']) }}<br />
                    </div>
                    <div class="form-group">
                        <label for="entriesTime">掲載期間</label>
                        <div class="form-inline">
                            <input type="text" name="start_at_date" value="{{ old('start_at_date', $start_at_date ?? now()->format('Y-m-d')) }}" class="form-control" id="StartAt">
                            <input type="time" name="start_at_time" value="{{ old('start_at_time', $start_at_time ?? now()->format('H:i')) }}" class="form-control">
                            ~
                            <input type="text" name="stop_at_date" value="{{ old('stop_at_date', $stop_at_date ?? '9999-12-31') }}" class="form-control" id="StopAt">
                            <input type="time" name="stop_at_time" value="{{ old('stop_at_time', $stop_at_time ?? '23:59') }}" class="form-control">                        
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ContentPriority">表示順</label><br />
                        {{ Tag::formNumber('sort', old('sort', $recommend_program['sort']), ['class' => 'form-control', 'required' => 'required', 'id' => 'ContentPriority']) }}<br />
                    </div>
                </div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-lg">送信</button>
        </div>
    </fieldset>
</form>
@endsection
