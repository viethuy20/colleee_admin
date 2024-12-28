@extends('layouts.master')

@section('title', 'コンテンツ管理')

@section('menu')
<li>{{ Tag::link(route('popup_ads.index'), '登録プログラム一覧') }}</li>
<li{!! (isset($ads['id']) ? '' : ' class="active"') !!}>{{ Tag::link(route('popup_ads.create'), '新規プログラム登録') }}</li>
@endsection
@section('head.load')
<script type="text/javascript">
    
    //datetime picker
    $( function() {
        $( "#PopupStartAt" ).datepicker({
            dateFormat: "yy-mm-dd"
        });
        $( "#PopupStopAt" ).datepicker({
            dateFormat: "yy-mm-dd"
        });
    
      } );

        // <!--
        $(document).ready(function() {
            $('#PopupProgramId').change(function() {
                console.log($(this).val());
                getProgram($(this).val());
            });
        });

      function getProgram(id) {
            $.ajax({
                type: 'GET',
                data: {id: id},
                url: "{{ route('popup_ads.get_program') }}",
                scriptCharset: 'utf-8',
                dataType: 'json',
                beforeSend: function (xhr) {
                    var token = $('meta[name="csrf_token"]').attr('content');
                    if (token) {
                        return xhr.setRequestHeader('X-XSRF-TOKEN', token);
                    }
                },
                success: function(data) {
                    if (data['error'] || data['title']==null) {
                        return;
                    }
                    else {
                        $('#ProgramTitle').text(data['title']);
                        $('#ProgramTitle').val(data['title']);
                        return;
                    }
                },
            });
            return false;
        }
    
    
    // -->
    </script>
@endsection
@section('content')
  
@if (count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

{{ Tag::formOpen(['url' => route('popup_ads.store'), 'method' => 'post', 'files' => true, 'class' => 'LockForm']) }}
@csrf 
    <fieldset>
        @if (isset($ads['id']))
        <legend>トップポップアップ用プログラム編集</legend>
        <div class="form-group">
            <label>ID</label><br />
            {{ $ads['id'] }}
            {{ Tag::formHidden('id', $ads['id']) }}
        </div>
        @else
        <legend>トップポップアップ用プログラム登録</legend>
        @endif
   
        <div class="form-group">
            <label for="PopupDevices">対象デバイス</label><br />
            {{ Tag::formSelect('devices', config('map.device2'), old('devices', $ads['devices']), ['class' => 'form-control', 'id' => 'PopupDevices']) }}<br />
        </div>
        <div class="form-group">
            <label for="PopupProgramId">プログラムID</label><br />
            {{ Tag::formText('program_id', old('program_id', $ads['program_id']), ['class' => 'form-control', 'required' => 'required', 'id' => 'PopupProgramId']) }}<br />
        </div>
        <div class="form-group">
            <label for="PopupTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $ads['title']), ['class' => 'form-control', 'maxlength' => '256', 'required' => 'required', 'id' => 'ProgramTitle']) }}<br />
        </div>
        
        <div class="form-group">
            <label for="PopupPriority">表示順</label><br />
            {{ Tag::formNumber('priority', old('priority', $ads['priority']), ['class' => 'form-control', 'required' => 'required', 'id' => 'PopupPriority']) }}<br />
        </div>
        
        <div class="form-group">
            <label for="popupStart">掲載期間</label>
            <div class="form-inline">
                {{ Tag::formText('start_at_date', old('start_at_date', $ads['start_at_date'] ?? now()->format('Y-m-d')), ['class' => 'form-control', 'id' => 'PopupStartAt']) }}
                {{ Tag::formTime('start_at_time', old('start_at_time', $ads['start_at_time'] ?? now()->format('H:i')), ['class' => 'form-control']) }}
                ~
                {{ Tag::formText('stop_at_date', old('stop_at_date', $ads['stop_at_date'] ?? '9999-12-31'), ['class' => 'form-control', 'id' => 'PopupStopAt']) }}
                {{ Tag::formTime('stop_at_time', old('stop_at_time', $ads['stop_at_time'] ?? '23:59'), ['class' => 'form-control']) }}
            </div>
                
        </div>
        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}

@endsection
