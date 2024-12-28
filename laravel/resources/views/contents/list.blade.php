@extends('layouts.master')

@section('title', 'コンテンツ管理')

@section('menu')
<li class="active">{{ Tag::link(route('contents.list', ['spot' => $spot]), 'コンテンツ一覧') }}</li>
<li>{{ Tag::link(route('contents.create', ['spot' => $spot]), '新規コンテンツ登録') }}</li>
@endsection

@section('content')
<h2>{{ $spot->title }}</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    @php
    $now = Carbon\Carbon::now();
    $th_list = ['ID', 'タイトル', '状態'];
    $spot_data = json_decode($spot->data);
    $key_count = 0;
    @endphp
    @foreach($spot_data as $key => $info)
    @php
    $th_list[] = $info->label;
    ++$key_count;
    @endphp
    @endforeach
    @php
    $th_list = array_merge($th_list, ['表示順', '開始日時', '終了日時', '削除日']);
    @endphp
    <tr>
        <th class="actions">操作</th>
        @foreach ($th_list as $th)
        <th>{{ $th }}</th>
        @endforeach
    </tr>
    @forelse ($content_list as $index => $content)
    @php
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        1 => ['class' => 'warning', 'status' => '公開待ち'],
        2 => ['class' => 'danger', 'status' => '公開終了'],
        3 => ['class' => 'danger', 'status' => '削除']];
    
    $state_id = 0;
    if ($content->status == 1) {
        $state_id = 3;
    } elseif($content->start_at->gt($now)) {
        $state_id = 1;
    } elseif($content->stop_at->lt($now)) {
        $state_id = 2;
    }
    $content_data = json_decode($content->data);
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions" style="white-space:nowrap">
            @if ($content->status == 0)
            {{ Tag::link(route('contents.edit', ['content' => $content]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            @endif
            {{ Tag::formOpen(['url' => route('contents.destroy', ['content' => $content])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('削除', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('Delete ".$content->title."?');"]) }}
            {{ Tag::formClose() }}
        </td>
        <td>{{ $content->id }}&nbsp;</td>
        <td>{{ $content->title }}&nbsp;</td>
        <td>{{ $status_map[$state_id]['status'] }}&nbsp;</td>
        @foreach($spot_data as $key => $info)
        <td>
            @php
            $data_value = $content_data->{$key} ?? null;
            @endphp
            @if ($info->type == 'url')
            {!! htmlspecialchars($data_value ?? '', ENT_QUOTES, 'UTF-8', true) !!}
            @elseif ($info->type == 'img_url')
            @if (isset($data_value))
            {{ Tag::image($data_value, 'img', ['width' => '120px']) }}<br />
            {!! htmlspecialchars($data_value, ENT_QUOTES, 'UTF-8', true) !!}
            @endif
            @else
            {{ $data_value }}
            @endif
        </td>
        @endforeach
        <td>{{ $content->priority }}&nbsp;</td>
        <td>{{ $content->start_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $content->stop_at->year >= 9999 ? '' : $content->stop_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ isset($content->deleted_at) ? $content->deleted_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="{{ ($key_count + 8) }}">コンテンツは存在しません</td></tr>
    @endforelse
</table>

@endsection