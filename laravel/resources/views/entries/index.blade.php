@extends('layouts.master')

@section('title', 'プログラム管理')

@section('menu')
<li class="active">{{ Tag::link(route('entries.index'), '告知テキスト一覧') }}</li>
<li>{{ Tag::link(route('entries.create'), '告知テキスト追加') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('entries.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="status">状態</label>
        {{ Tag::formSelect('status', [
            '' => '---',
            \App\Entries::STATUS_END => '終了済み',
            \App\Entries::STATUS_START => '公開中',
            \App\Entries::STATUS_STANDBY => '公開待ち',
        ], $entries_list->getQuery('status') ?? null, ['class' => 'form-control', 'id' => 'mapStatus']) }}    
    </div>
    <div class="form-group">{!! Tag::formSubmit('検索', ['class' => 'btn btn-default']) !!}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>会員登録画面：告知テキスト管理</h2>
@if(isset($entries_list) && $entries_list->isNotEmpty())
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">

    <tr>
        <th style="width: 8%;">操作</th>
        <th style="width: 15%;">状態</th>
        <th style="width: 7%;">ID</th>
        <th style="width: 25%;">告知名称</th>
        <th style="width: 15%;">掲載期間</th>
        <th style="width: 15%;">作成日</th>
        <th style="width: 15%;">更新日</th>
    </tr>
    <tbody>
        @forelse ($entries_list as $index => $entries)
        @php
            $start_at = $entries['start_at'];
            $date_time = new DateTime($start_at);
            $start_at_date = $date_time->format('Y-m-d');
            $start_at_time = $date_time->format('H:i:s');
            $stop_at = $entries['stop_at'];
            $date_time = new DateTime($stop_at);
            $stop_at_date = $date_time->format('Y-m-d');
            $stop_at_time = $date_time->format('H:i:s');
            //get status
            $set_date   = date('Y-m-d H:i:s');
            if($entries['start_at'] < $set_date && $entries['stop_at'] < $set_date) {
                // 終了済み
                $entries['status'] = \App\Entries::STATUS_END;
            } else if($entries['start_at'] <= $set_date && $entries['stop_at'] >= $set_date) {
                // 公開中
                $entries['status'] = \App\Entries::STATUS_START;
            } else if($entries['start_at'] > $set_date && $entries['stop_at'] > $set_date) {
                // 公開待ち
                $entries['status'] = \App\Entries::STATUS_STANDBY;
            }

        @endphp
        <tr>
            <td >
                {{ Tag::link(route('entries.edit',['entry' => $entries->id]), '参照', ['class' => 'btn btn-small btn-success']) }}<br />
            </td>
            <td>
                @if ($entries->status == \App\Entries::STATUS_END)
                終了済み
                @elseif ($entries->status == \App\Entries::STATUS_START)
                公開中
                @elseif ($entries->status == \App\Entries::STATUS_STANDBY)
                公開待ち
                @endif
                </td>
            <td>
                {{ $entries['id'] }}
            </td>
            <td>
                {{ $entries['title'] }}
            </td>
            <td>
                {{ $start_at_date . '  ' . $start_at_time }} ~ <br>
                {{ $stop_at_date . '  ' . $stop_at_time }}
            </td>
            <td>
                {{ $entries['created_at'] }}
            </td>
            <td>
                {{ $entries['updated_at'] }}
            </td>
        </tr>
        @empty
        <tr><td colspan="7">プログラムは存在しません</td></tr>
        @endforelse
    </tbody>
   
</table>
@endif
@endsection
