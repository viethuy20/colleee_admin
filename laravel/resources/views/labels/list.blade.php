@extends('layouts.master')

@section('title', 'ラベル管理')

@section('head.load')

<script type="text/javascript">
<!--
$(function(){
    $('#LabelDialog').dialog({
        autoOpen: false,
        title: 'ラベル編集',
        closeOnEscape: true,
        modal: true,
        minWidth: 600,
        minHeight: 600,
        close : function() {
            var label_id = $('#LabelDialog').contents().find('#LabelId').val();
            var label_name = $('#LabelDialog').contents().find('#InputName').val();
            $('#Label'+label_id).text(label_name);
        }
    });

    $('.childList').hide();
    $('.showList').on('click', function(event) {
        var label_id = $(this).next('ul').attr('id').replace('ParentLabel','');
        $('#ParentLabel'+label_id).toggle();
        return false;
    });
    $('.AjaxTag').on('click', function(event) {
        event.preventDefault();
        var label_id = $(this).attr('id').replace('Label','');
        var url = "{{ config('app.url') }}/labels/" + label_id + "/edit";
        var dialog = $('#LabelDialog');
        dialog.attr('src', url);
        dialog.show();
        dialog.dialog('open');
    });
});
// -->
</script>

@endsection

@section('menu')
@php
$label_type = config('map.label_type');
@endphp
@foreach($label_type as $key => $label)
<li{!! ($type == $key) ? ' class="active"' : '' !!}>{{ Tag::link(route('labels.list', ['type' => $key]), $label) }}</li>
@endforeach
@endsection

@section('content')
<iframe src="" id="LabelDialog" style="min-width:600px;min-height:600px;"></iframe>
<h2>ラベル</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    @php
    $th_list = [['key' => 1, 'name' => '編集'], ['key' => 2, 'name' => 'タイプ']];
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        1 => ['class' => 'danger', 'status' => '非公開']];
    @endphp
    <tr>
        <th class="actions">操作</th>
        @foreach ($th_list as $th)
        <th>{{ $th['name'] }}</th>
        @endforeach
    </tr>
    @php
    $th_list = [['key' => 1, 'name' => '名称'], ['key' => 2, 'name' => 'タイプ']];
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        1 => ['class' => 'danger', 'status' => '非公開']];
    @endphp
    @forelse ($label_list as $index => $label)
    @php
    $state_id = 0;
    if ($label->status == 1) {
        $state_id = 1;
    }
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions" style="white-space:nowrap">
            @if ($label->status != 0)
            {{ Tag::formOpen(['url' => route('labels.enable', ['label' => $label])]) }}
            @csrf    
            {{ Tag::formSubmit('公開', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('このラベルを公開しますか?:".$label->name."?');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($label->status != 1)
            {{ Tag::formOpen(['url' => route('labels.destroy', ['label' => $label])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('非公開', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('このラベルを非公開にしますか?:".$label->title."?');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td>
            <a href="#"  class="AjaxTag" id="Label{{$label->id}}"}>{{ $label->name }}</a>
            @include('elements.label_show', ['label' => $label])
        </td>
        <td>{{ config('map.label_type')[$label->type]}}</td>
    </tr>
    @empty
    <tr><td colspan="5">ラベルは存在しません</td></tr>
    @endforelse
</table>
<p>{{ $label_list->count() }}件</p>
@endsection
