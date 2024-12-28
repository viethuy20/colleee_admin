@extends('layouts.master')

@section('title', 'プログラムページ用キャンペーン登録')

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
    $('#entriesMainTextSP').on('input', function() {    
        let element = $(this);   
        let lines = element.val().split('\n');    
        let totalLength = 0;    
        let newLines = [];   
        for (let i = 0; i < lines.length; i++) {    
            if (totalLength >= 40) break; 
            if (lines[i].length > 20) {    
                lines[i] = lines[i].substring(0, 20); 
            }    
            let lengthToAdd = Math.min(lines[i].length, 40 - totalLength);    
            totalLength += lengthToAdd;  
            newLines.push(lines[i].substring(0, lengthToAdd));
            if (totalLength >= 40) break;
        }     
        element.val(newLines.join('\n'));    
    });    
});   

// -->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('entries.index'), 'プログラム一覧') }}</li>
<li class="active">{{ Tag::link(route('entries.create'), '告知テキスト追加') }}</li>
@endsection

@section('menu.extra')
@endsection
@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif
@php
if(isset($entry->id)){
    $start_at = $entry['start_at'];
    $date_time = new DateTime($start_at);
    $start_at_date = $date_time->format('Y-m-d');
    $start_at_time = $date_time->format('H:i');
    $stop_at = $entry['stop_at'];
    $date_time = new DateTime($stop_at);
    $stop_at_date = $date_time->format('Y-m-d');
    $stop_at_time = $date_time->format('H:i');
}

@endphp
<!-- プログラム複写 -->

<form action="{{ route('entries.store') }}" method="post" enctype="multipart/form-data" id="LockFormId" class="LockForm">
    @csrf
    <fieldset>
    <legend>
        @if (isset($entry->id))
        <input type="hidden" name="id" value="{{ $entry->id }}">        
        @endif
        会員登録画面：告知テキスト追加・編集
    </legend>

        <div id="entries_detail">
                <div class="entries-group">
                    <div class="form-group">
                        <label for="entriesTitle">告知名称</label>
                        <input type="text" name="title" required value="{{ old('title', $entry['title'] ?? '') }}" class="form-control" required id="EntriesTitle">
                    </div>
                    <div class="form-group">
                        <label for="entriesMainTextPC">PCテキスト（メイン）※全角30文字まで</label>
                        <textarea name="main_text_pc" class="form-control" required id="entriesMainTextPC"  maxlength="30">{{ old('main_text_pc', $entry['main_text_pc'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="entriesSubTextPC">PCテキスト（サブ）※全角50文字まで</label>
                        <textarea name="sub_text_pc" class="form-control" required id="entriesSubTextPC" maxlength="50">{{ old('sub_text_pc', $entry['sub_text_pc'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="entriesMainTextSP">SPテキスト（メイン）※1行20文字まで改行あり</label>
                        <textarea name="main_text_sp" class="form-control" required id="entriesMainTextSP"  >{{ old('main_text_sp', $entry['main_text_sp'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="entriesSubTextSP">SPテキスト（サブ）※全角25文字まで</label>
                        <textarea name="sub_text_sp" class="form-control" required  maxlength="25">{{ old('sub_text_sp', $entry['sub_text_sp'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="entriesTime">掲載期間</label>
                        <div class="form-inline">
                            <input type="text" name="start_at_date" value="{{ old('start_at_date', $start_at_date ?? now()->format('Y-m-d')) }}" class="form-control" id="EntriesStartAt">
                            <input type="time" name="start_at_time" value="{{ old('start_at_time', $start_at_time ?? now()->format('H:i')) }}" class="form-control">
                            ~
                            <input type="text" name="stop_at_date" value="{{ old('stop_at_date', $stop_at_date ?? '9999-12-31') }}" class="form-control" id="EntriesStopAt">
                            <input type="time" name="stop_at_time" value="{{ old('stop_at_time', $stop_at_time ?? '23:59') }}" class="form-control">                        
                        </div>
                    </div>
                </div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-lg">送信</button>
        </div>
    </fieldset>
</form>
@endsection
