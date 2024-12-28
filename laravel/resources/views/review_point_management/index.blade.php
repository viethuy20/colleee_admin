@extends('layouts.master')

@section('title', 'ポイント管理一覧')

@section('menu')
<li>{{ Tag::link(route('review_point_management.index'), 'ポイント管理一覧') }}</li>
<li>{{ Tag::link(route('review_point_management.create'), 'ポイント管理追加') }}</li>
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
$count = 1; // 初期カウントを設定
$totalItems = WrapPhp::count($paginator);
@endphp
@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('review_point_management.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="FriendRequestStatus">状態</label>
        {{ Tag::formSelect('status', ['' => '全て'] + $status_map, $paginator->getQuery('status') ?? '', ['class' => 'form-control', 'id' => 'FriendRequestStatus']) }}
    </div>
    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>配布ポイント管理</h2>
<p class="alert alert-info">
    ・期間に含まれない場合はデフォルトの5ポイントが適用されます。<br>
    ・編集できるのは最新のスケジュールのみとなります。
</p>

<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>操作</th>
        <th>状態</th>
        <th>口コミ配布ポイント数</th>
        <th>期間</th>
        <th>作成日<br/>更新日</th>
    </tr>
    @foreach ($paginator as $index => $bonus_data)

    <tr>
        <th>
        @php
            if($bonus_data['start_at'] < $set_date && $bonus_data['stop_at'] && $bonus_data['stop_at'] < $set_date) {
                // 終了済み
                $bonus_data['status'] = \App\ReviewPointManagement::STATUS_END;
            } else if($bonus_data['start_at'] <= $set_date) {
                // 公開中
                $bonus_data['status'] = \App\ReviewPointManagement::STATUS_START;
            } else if($bonus_data['start_at'] > $set_date) {
                // 公開待ち
                $bonus_data['status'] = \App\ReviewPointManagement::STATUS_STANDBY;
            }
        @endphp
        @if ($bonus_data->status == \App\ReviewPointManagement::STATUS_STANDBY)
            @if ($count == 1) <!-- Check if this is the first record -->
                <a href="{{ route('review_point_management.show', $bonus_data->id) }}" class="btn btn-small btn-success">参照</a>
            @endif
            {{ Tag::formOpen(['route' => ['review_point_management.destroy', $bonus_data->id], 'style' => 'display:inline']) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('削除', ['class' => 'btn btn-small btn-danger', 'onclick' => 'return confirm("本当に削除してもよいですか?");']) }}
            {{ Tag::formClose() }}
        @endif
        </th>
        <td>
        @if ($bonus_data->status == \App\ReviewPointManagement::STATUS_END)
        終了済み
        @elseif ($bonus_data->status == \App\ReviewPointManagement::STATUS_START)
        公開中
        @elseif ($bonus_data->status == \App\ReviewPointManagement::STATUS_STANDBY)
        公開待ち
        @endif
        </td>
        <td>{{ number_format($bonus_data->point) }}</td>
        <td style="{{ $bonus_data->status == \App\ReviewPointManagement::STATUS_START ? 'background-color: yellow' : '' }}">
        {{ $bonus_data->start_at }} ～<br/>{{ $bonus_data->stop_at }}</td>
        <td>{{ $bonus_data->created_at }}<br/>{{ $bonus_data->updated_at }}</td>
    </tr>
    @php
        $count++; // カウントをインクリメント
    @endphp
    @endforeach
</table>

{!! $paginator->links() !!}

@endsection
