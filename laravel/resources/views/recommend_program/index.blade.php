@extends('layouts.master')

@section('title', 'おすすめ広告管理')

@section('menu')
<li class="active">{{ Tag::link(route('recommend_program.index'), 'おすすめ広告一覧') }}</li>
<li>{{ Tag::link(route('recommend_program.create'), 'おすすめ広告追加') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('recommend_program.index'), 'method' => 'get']) }}
    <div class="form-group">
        <label for="status">状態</label>
        {{ Tag::formSelect('status', [
            '' => '---',
            \App\Entries::STATUS_END => '終了済み',
            \App\Entries::STATUS_START => '公開中',
            \App\Entries::STATUS_STANDBY => '公開待ち',
        ], $recommend_program_list->getQuery('status') ?? null, ['class' => 'form-control', 'id' => 'mapStatus']) }}
    </div>
    <div class="form-group">{!! Tag::formSubmit('検索', ['class' => 'btn btn-default']) !!}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
<h2>おすすめ広告管理</h2>
@if(isset($recommend_program_list) && $recommend_program_list->isNotEmpty())
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">

    <tr>
        <th style="width: 8%;">操作</th>
        <th style="width: 15%;">状態</th>
        <th style="width: 5%;">プログラムID</th>
        <th style="width: 25%;">告知名称</th>
        <th style="width: 15%;">掲載期間</th>
        <th style="width: 5%;">ソート順(昇順)</th>
        <th style="width: 15%;">作成日</th>
        <th style="width: 15%;">更新日</th>
    </tr>
    <tbody>
        @forelse ($recommend_program_list as $index => $recommend_program)
        @php
            $start_at = $recommend_program['start_at'];
            $date_time = new DateTime($start_at);
            $start_at_date = $date_time->format('Y-m-d');
            $start_at_time = $date_time->format('H:i:s');
            $stop_at = $recommend_program['stop_at'];
            $date_time = new DateTime($stop_at);
            $stop_at_date = $date_time->format('Y-m-d');
            $stop_at_time = $date_time->format('H:i:s');
            //get status
            $set_date   = date('Y-m-d H:i:s');
            if($recommend_program['start_at'] < $set_date && $recommend_program['stop_at'] < $set_date) {
                // 終了済み
                $recommend_program['status'] = \App\RecommendProgram::STATUS_END;
            } else if($recommend_program['start_at'] <= $set_date && $recommend_program['stop_at'] >= $set_date) {
                // 公開中
                $recommend_program['status'] = \App\RecommendProgram::STATUS_START;
            } else if($recommend_program['start_at'] > $set_date && $recommend_program['stop_at'] > $set_date) {
                // 公開待ち
                $recommend_program['status'] = \App\RecommendProgram::STATUS_STANDBY;
            }

        @endphp
        <tr>
            <td class="actions" style="white-space:nowrap">
                {{ Tag::link(route('recommend_program.edit',['recommend_program' => $recommend_program->id]), '参照', ['class' => 'btn btn-small btn-success']) }}<br />
                {{ Tag::link(route('recommend_program.destroy',['id' => $recommend_program->id]), '削除', ['class' => 'btn btn-small btn-danger','onclick' => "return confirm('本当に削除しますか？')"]) }}
            </td>
            <td>
                @if ($recommend_program->status == \App\RecommendProgram::STATUS_END)
                終了済み
                @elseif ($recommend_program->status == \App\RecommendProgram::STATUS_START)
                公開中
                @elseif ($recommend_program->status == \App\RecommendProgram::STATUS_STANDBY)
                公開待ち
                @endif
                </td>
            <td>
                {{ $recommend_program['program_id'] }}
            </td>
            <td>
                {{ $recommend_program['title'] }}
            </td>
            <td>
                {{ $start_at_date . '  ' . $start_at_time }} ~ <br>
                {{ $stop_at_date . '  ' . $stop_at_time }}
            </td>
            <td>
                {{ $recommend_program['sort'] }}
            </td>
            <td>
                {{ $recommend_program['created_at'] }}
            </td>
            <td>
                {{ $recommend_program['updated_at'] }}
            </td>
        </tr>
        @empty
        <tr><td colspan="7">プログラムは存在しません</td></tr>
        @endforelse
    </tbody>

</table>
@endif
@endsection
