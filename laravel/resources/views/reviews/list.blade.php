@extends('layouts.master')

@section('title', '口コミ管理')

@section('head.load')
<script type="text/javascript">
<!--
$(function() {
    $('#allChk').on('click', function() {
        $('.edit').prop('checked', this.checked);
    });
});

function collectiveUpdateStatus(status){
    var hasChecked = false;
    $('.edit:checked').each(function(){
        hasChecked = true;
    }).get();

    // 
    if (!hasChecked){
        alert('口コミが選択されていません。');
        return false;
    }
    
    if (!confirm('\nチェックした口コミを更新しますか？')){
        return false;
    }
    
    $("#ReviewChangeStatusStatus").val(status);
    $("#ReviewChangeStatusForm").submit();
    return true;
}
//--!>
</script>
@endsection

@section('menu')
<li{!! ($target_status == -1) ? ' class="active"' : '' !!}>{{ Tag::link(route('reviews.index'), '全一覧') }}</li>
@php 
$review_status_map = config('map.auth_status');
@endphp
@foreach($review_status_map as $i =>  $label)
<li{!! ($target_status == $i) ? ' class="active"' : '' !!}>{{ Tag::link(route('reviews.list', ['status' => $i]), $label.'一覧') }}</li>
@endforeach
<li><a href="{{ route('review_point_management.index') }}">配布ポイント管理</a></li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('reviews.list', ['status' => $target_status]), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="ProgramId">プログラムID</label>
        {{ Tag::formText('program_id', $paginator->getQuery('program_id') ?? '', ['class' => 'form-control', 'id' => 'ProgramId']) }}
    </div>
    <div class="form-group">
        <label for="UserName">ユーザーID</label>
        {{ Tag::formText('user_name', $paginator->getQuery('user_name') ?? '', ['class' => 'form-control', 'id' => 'UserName']) }}
    </div>
    <div class="form-group">
        <label for="Ip">IPアドレス</label>
        {{ Tag::formText('ip', $paginator->getQuery('ip') ?? '', ['class' => 'form-control', 'id' => 'Ip']) }}
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>口コミ情報</h2>

{{ Tag::formOpen(['method' => 'POST', 'url' => route('reviews.change_status'), 'id' => 'ReviewListForm']) }}
@csrf   
{{ Tag::formHidden('status', '', ['id' => 'ReviewChangeStatusStatus']) }}
    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
        <tr>
            <th rowspan="3">
                {{ Tag::formCheckbox('allChk', 'allChk', null, ['id' => 'allChk']) }}全選択
            </th>
            <th rowspan="3">状態</th>
            <th>投稿者<br />(ユーザーID)</th>
            <th>プログラムID</th>
            <th>広告名</th>
            <th>ポイント配付日</th>
        </tr>
        <tr>
            <th>評価</th>
            <th colspan="2">コメント</th>
            <th>投稿日</th>
        </tr>
        <tr>
            <th>IPアドレス</th>
            <th colspan="2">ユーザーエージェント</th>
            <th>更新日</th>
        </tr>
        @php 
        $class_map = [0 => 'active', 2 => 'warning', 1 => 'danger'];
        @endphp
        @forelse ($paginator as $index => $review)
        <tr class="{{ $class_map[$review->status] }}">
            <td class="actions" rowspan="3">{{ Tag::formCheckbox('id[]', $review->id, null, ['class'=>'edit']) }}</td>
            <td rowspan="3">{{ $review_status_map[$review->status] }}</td>
            <td>{{ $review->reviewer }}<br />({{ Tag::link(route('users.edit', ['user' => $review->user_id]), $review->user_name) }})</td>
            <td>{{ $review->program_id }}</td>
            <td>{{ $review->program->title }}</td>
            <td>{{ isset($review->pointed_at) ? $review->pointed_at->format('Y-m-d H:i:s') : '-' }}</td>
        </tr>
        <tr class="{{ $class_map[$review->status] }}">
            <td>{{ str_repeat('★', $review->assessment).str_repeat('☆', (5 - $review->assessment)) }}</td>
            <td class="col-md-6" colspan="2" style="word-break: break-all;">{{ $review->message }}</td>
            <td>{{ isset($review->created_at) ? $review->created_at->format('Y-m-d H:i:s') : '-' }}</td>
        </tr>
        <tr class="{{ $class_map[$review->status] }}">
            <td>{{ $review->ip }}</td>
            <td class="col-md-6" colspan="2" style="word-break: break-all;">{{ $review->ua }}</td>
            <td>{{ isset($review->updated_at) ? $review->updated_at->format('Y-m-d H:i:s') : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6">口コミは存在しません</td></tr>
        @endforelse
    </table>

    チェック入れたものを操作<br />
    {{ Tag::formSubmit('承認', ['id' => 'approval_btn', 'class' => 'btn btn-success btn-small', 'style' => 'float:left;', 'onclick' => "return collectiveUpdateStatus(0);"]) }}
    {{ Tag::formSubmit('却下', ['id' => 'reject_btn', 'class' => 'btn btn-danger btn-small', 'onclick' => "return collectiveUpdateStatus(1);"]) }}
{{ Tag::formClose() }}

{!! $paginator->links() !!}

@endsection
