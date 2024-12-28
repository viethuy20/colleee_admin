@extends('layouts.master')

@section('title', 'お友達紹介管理')

@section('menu')
<li>{{ Tag::link(route('friends.index'), 'スケジュール一覧') }}</li>
<li>{{ Tag::link(route('friends.newdata'), 'スケジュール追加') }}</li>
@endsection

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

@php
$status_map = config('friends.status');
$set_date   = date('Y-m-d H:i:s');
@endphp
@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('friends.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="FriendRequestStatus">状態</label>
        {{ Tag::formSelect('status', ['' => '全て'] + $status_map, $paginator->getQuery('status') ?? '', ['class' => 'form-control', 'id' => 'FriendRequestStatus']) }}
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>お友達紹介管理</h2>

<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>操作</th>
        <th>状態</th>
        <th>ID</th>
        <th>ポイント付与名称</th>
        <th>紹介ポイント数</th>
        <th>付与ポイント数</th>
        <th>紹介掲載期間</th>
        <th>作成日<br/>更新日</th>
    </tr>
    @foreach ($paginator as $bonus_data)
    <tr>
        <th>

        @php
            if($bonus_data['start_at'] < $set_date && $bonus_data['stop_at'] < $set_date) {
                // 終了済み
                $bonus_data['status'] = \App\FriendReferralBonusSchedule::STATUS_END;
            } else if($bonus_data['start_at'] <= $set_date && $bonus_data['stop_at'] >= $set_date) {
                // 公開中
                $bonus_data['status'] = \App\FriendReferralBonusSchedule::STATUS_START;
            } else if($bonus_data['start_at'] > $set_date && $bonus_data['stop_at'] > $set_date) {
                // 公開待ち
                $bonus_data['status'] = \App\FriendReferralBonusSchedule::STATUS_STANDBY;
            }
        @endphp
        @if ($bonus_data->status == \App\FriendReferralBonusSchedule::STATUS_START  ||
             $bonus_data->status == \App\FriendReferralBonusSchedule::STATUS_STANDBY)
            {{ Tag::formOpen(['url' => route('friends.show', ['id' => $bonus_data->id])]) }}
            @csrf    
            {{ Tag::formSubmit('参照', ['class' => 'btn btn-small btn-success']) }}
            {{ Tag::formClose() }}
        @endif
        </th>
        <td>
        @if ($bonus_data->status == \App\FriendReferralBonusSchedule::STATUS_END)
        終了済み
        @elseif ($bonus_data->status == \App\FriendReferralBonusSchedule::STATUS_START)
        公開中
        @elseif ($bonus_data->status == \App\FriendReferralBonusSchedule::STATUS_STANDBY)
        公開待ち
        @endif
        </td>
        <td>{{ $bonus_data->id }}</td>
        <td>{{ $bonus_data->name }}</td>
        <td>{{ number_format($bonus_data->reward_condition_point) }}</td>
        <td>{{ number_format($bonus_data->friend_referral_bonus_point) }}</td>
        <td style="{{ $bonus_data->status == \App\FriendReferralBonusSchedule::STATUS_START ? 'background-color: yellow' : '' }}">
        {{ $bonus_data->start_at }} ～<br/>{{ $bonus_data->stop_at }}</td>
        <td>{{ $bonus_data->created_at }}<br/>{{ $bonus_data->updated_at }}</td>
    </tr>
    @endforeach
</table>

{!! $paginator->links() !!}

@endsection
