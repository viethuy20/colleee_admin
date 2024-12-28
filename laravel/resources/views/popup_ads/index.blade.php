@extends('layouts.master')

@section('title', 'コンテンツ管理')

@section('menu')
<li class="active">{{ Tag::link(route('popup_ads.index'), '登録プログラム一覧') }}</li>
<li>{{ Tag::link(route('popup_ads.create'), '新規プログラム登録') }}</li>
@endsection

@section('content')
<h2>トップポップアップ用登録プログラム一覧</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th class="actions">操作</th>
        <th>ID</th>
        <th>デバイス</th>
        <th>プログラムID</th>
        <th>タイトル</th>
        <th>状態</th>
        <th>表示順</th>
        <th>開始日時</th>
        <th>終了日時</th>
        <th>削除日</th>

    @forelse ($paginator as $index => $ads)
    @php
    $now = Carbon\Carbon::now();
    $status = '公開中';
    if($ads['start_at']->gt($now)) {
        $status = '公開待ち';
    } elseif($ads['stop_at']->lt($now)) {
        $status = '終了';
    }
    @endphp
    <tr class="">
        <td class="actions" style="white-space:nowrap">
            {{ Tag::link(route('popup_ads.edit', ['popup_ad' => $ads]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            {{ Tag::formOpen(['url' => route('popup_ads.destroy', ['popup_ad' => $ads])]) }}
            @csrf
            @method('DELETE')    
            {{ Tag::formSubmit('削除', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('Delete ".$ads['title']."?');"]) }}
            {{ Tag::formClose() }}
        </td>
        <td>{{ $ads->id }}&nbsp;</td>
        <td>{{ config('map.device2')[$ads->devices] }}&nbsp;</td>
        <td>{{ $ads->program_id }}&nbsp;</td>
        <td>{{ $ads->title }}&nbsp;</td>
        <td>{{ $status }}&nbsp;</td>
        <td>{{ $ads->priority }}&nbsp;</td>
        {{-- <td>{{ $status_map[$state_id]['status'] }}&nbsp;</td> --}}

        <td>{{ $ads->start_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $ads->stop_at->year >= 9999 ? '' : $ads->stop_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ isset($ads->deleted_at) ? $ads->deleted_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="7">プログラムは存在しません</td></tr>    @endforelse
</table>
{!! $paginator->links() !!}
@endsection