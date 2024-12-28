@extends('layouts.master')

@section('title', 'メンテナンス管理')

@section('menu')
<li class="active">{{ Tag::link(route('maintes.index'), 'メンテナンス管理') }}</li>
@endsection

@section('content')
<h2>メンテナンス</h2>

<table cellpadding="0" cellspacing="0" class="table">
    <tr>
        <th class="actions">操作</th>
        <th>ID</th>
        <th>名称</th>
        <th>内容</th>
    </tr>
    @php
    $mainte_type_map = config('mainte.type');
    @endphp
    @forelse ($mainte_type_map as $mainte_type => $mainte_name)
    <tr>
        <td class="actions">
            {{ Tag::link(route('maintes.create', ['type' => $mainte_type]), '追加', ['class' => 'btn btn-small btn-success']) }}
        </td>
        <td>{{ $mainte_type }}&nbsp;</td>
        <td>{{ $mainte_name }}&nbsp;</td>
        <td>
            @php
            $mainte_list = $mainte_map[$mainte_type] ?? null;
            @endphp
            @if (!empty($mainte_list))
            <table cellpadding="0" cellspacing="0" class="table">
                <tr>
                    <th class="actions">操作</th>
                    <th>状態</th>
                    <th>公開日時</th>
                    <th>内容</th>
                </tr>
                @foreach ($mainte_list as $mainte)
                <tr>
                    <td>
                        @if ($mainte->editable)
                        {{ Tag::link(route('maintes.edit', ['mainte' => $mainte]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
                        @endif
                        @if ($mainte->status != 0 || $mainte->stop_at->gte(\Carbon\Carbon::now()))
                        {{ Tag::formOpen(['url' => route('maintes.destroy', ['mainte' => $mainte])]) }}
                        @csrf
                        @method('DELETE')
                        {{ Tag::formSubmit('解除', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('このメンテナンスを無効化にしますか?:".$mainte->message."?');"]) }}
                        {{ Tag::formClose() }}
                        @endif
                    </td>
                    <td>
                        @if ($mainte->status != 0 || $mainte->stop_at->lte(\Carbon\Carbon::now()))
                        解除
                        @else
                        @if ($mainte->start_at->gte(\Carbon\Carbon::now()))
                        公開準備中
                        @else
                        公開中
                        @endif
                        @endif
                    </td>
                    <td>{{ $mainte->start_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $mainte->message }}</td>
                </tr>
                @endforeach
            </table>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="4">メンテナンスは存在しません</td></tr>
    @endforelse
</table>
@endsection
