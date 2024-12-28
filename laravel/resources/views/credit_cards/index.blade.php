@extends('layouts.master')

@section('title', 'クレジットカード一覧')

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li class="active">{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
<li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
<li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
@php
$sp_program_type_list = \App\SpProgramType::get();
@endphp
@foreach($sp_program_type_list as $sp_program_type)
<li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
@endforeach
@endsection

@php
// 共通ポイント
$point_type_map = config('map.credit_card_point_type');
// ブランド
$brand_map = config('map.credit_card_brand');
// 電子マネー
$emoney_map = config('map.credit_card_emoney');
// 付帯保険
$insurance_map = config('map.credit_card_insurance');
// ApplePay
$apple_pay_map = config('map.credit_card_apple_pay');
@endphp

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('credit_cards.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group">
        <label for="CreditCardTitle">タイトル</label>
        {{ Tag::formText('title', $paginator->getQuery('title') ?? '', ['class' => 'form-control', 'id' => 'CreditCardTitle']) }}
    </div>
    <div class="form-group">
        <label for="CreditCardStartAt">開始日</label>
        {{ Tag::formText('start_at', $paginator->getQuery('start_at') ?? '', ['class' => 'form-control', 'id' => 'CreditCardStartAt']) }}
    </div>
    <div class="form-group">
        <label for="CreditCardStopAt">終了日</label>
        {{ Tag::formText('stop_at', $paginator->getQuery('stop_at') ?? '', ['class' => 'form-control', 'id' => 'CreditCardStopAt']) }}
    </div>
    <div class="form-group">
        <label for="ProgramDevice">ブランド</label>
        {{ Tag::formSelect('brand', ['0' => '---'] + $brand_map, $paginator->getQuery('brand') ?? 0, ['class' => 'form-control', 'id' => 'CreditCardBrand']) }}
    </div>
    <div class="form-group">
        <label for="ProgramDevice">年会費</label>
        {{ Tag::formSelect('annual_free', ['' => '---', '1' => '永年無料'], $paginator->getQuery('annual_free') ?? '', ['class' => 'form-control', 'id' => 'CreditCardAnnual']) }}
    </div>
    <div class="form-group">
        <label for="CreditCardEmoney">電子マネー</label>
        {{ Tag::formSelect('emoney', ['0' => '---'] + $emoney_map, $paginator->getQuery('emoney') ?? 0, ['class' => 'form-control', 'id' => 'CreditCardEmoney']) }}
    </div>
    <div class="form-group">
        <label for="ProgramDevice">ETC</label>
        {{ Tag::formSelect('etc', ['' => '---', '1' => 'ETC付き'], $paginator->getQuery('etc') ?? '', ['class' => 'form-control', 'id' => 'CreditCardEtc']) }}
    </div>
    <div class="form-group">
        <label for="ProgramDevice">ApplePay</label>
        {{ Tag::formSelect('apple_pay', ['' => '---'] + $apple_pay_map, $paginator->getQuery('apple_pay') ?? '', ['class' => 'form-control', 'id' => 'CreditCardApplePay']) }}
    </div>
    <div class="form-group">
        <label for="CreditCardInsurance">付帯保険</label>
        {{ Tag::formSelect('insurance', ['0' => '---'] + $insurance_map, $paginator->getQuery('insurance') ?? 0, ['class' => 'form-control', 'id' => 'CreditCardInsurance']) }}
    </div>

    <div class="form-group">{{ Tag::formSubmit('検索', ['class' => 'btn btn-default']) }}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>クレジットカード</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th rowspan="2">操作</th>
        <th rowspan="2">プログラムID</th>
        <th>タイトル</th>
        <th rowspan="2">共通ポイント</th>
        <th rowspan="2">ブランド</th>
        <th>年会費</th>
        <th>ETC</th>
        <th rowspan="2">電子マネー</th>
        <th rowspan="2">付帯保険</th>
        <th>開始日</th>
        <th>作成日</th>
        <th rowspan="2">削除日</th>
    </tr>
    <tr>
        <th>プログラム</th>
        <th>ポイント還元率</th>
        <th>ApplePay</th>
        <th>終了日</th>
        <th>更新日</th>
    </tr>
    @php
    $now = Carbon\Carbon::now();
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        3 => ['class' => 'danger', 'status' => '公開終了'],
        4 => ['class' => 'danger', 'status' => '非公開']];
    @endphp
    @forelse ($paginator as $index => $credit_card)
    @php
    $state_id = 0;
    if ($credit_card->status != null) {
        if ($credit_card->status == 1 || $credit_card->program->status == 1) {
        $state_id = 4;
        } elseif ($credit_card->stop_at->lt($now)) {
            $state_id = 3;
        }
    }
    
    // 共通ポイント
    $point_types = [];
    foreach ($credit_card->point_map as $type => $detail) {
        $point_types[] = $point_type_map[$type] ?? '';
    }
    // ブランド
    $brands = [];
    foreach($credit_card->brand as $id) {
        $brands[] = $brand_map[$id];
    }
    // 電子マネー
    $emoneys = [];
    foreach($credit_card->emoney as $id) {
        $emoneys[] = $emoney_map[$id];
    }
    // 付帯保険
    $insurances = [];
    foreach($credit_card->insurance as $id) {
        $insurances[] = $insurance_map[$id];
    }
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td rowspan="2" class="actions" style="white-space:nowrap">
            @if(isset($credit_card->program))
            {{ Tag::link(route('credit_cards.edit', ['program' => $credit_card->program]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            @endif
            @if ($credit_card->status != 0 && isset($credit_card->program))
            {{ Tag::formOpen(['url' => route('credit_cards.enable', ['program' => $credit_card->program])]) }}
            @csrf    
            {{ Tag::formSubmit('公開', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('このクレジットカードを公開しますか？');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($credit_card->status != 1 && isset($credit_card->program))
            {{ Tag::formOpen(['url' => route('credit_cards.destroy', ['program' => $credit_card->program])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('非公開', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('このクレジットカードを非公開にしますか？');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>
        <td rowspan="2">{{ $credit_card->program_id }}&nbsp;</td>
        <td>{{ $credit_card->title }}&nbsp;</td>
        <td rowspan="2">{!! isset($point_types) ? nl2br(e(implode("\n", $point_types))) : '' !!}&nbsp;</td>&nbsp;</td>
        <td rowspan="2">{!! isset($brands) ? nl2br(e(implode("\n", $brands))) : '' !!}&nbsp;</td>
        <td>
            @if ($credit_card->annual_free == 1)
            永年無料&nbsp;
            @else
            {{ $credit_card->annual_detail }}&nbsp;
            @endif
        </td>
        <td>
            @if ($credit_card->etc == 1)
            {{ $credit_card->etc_detail }}&nbsp;
            @else
            なし
            @endif
        </td>
        <td rowspan="2">{!! isset($emoneys) ? nl2br(e(implode("\n", $emoneys))) : '' !!}&nbsp;</td>
        <td rowspan="2">{!! isset($insurances) ? nl2br(e(implode("\n", $insurances))) : '' !!}&nbsp;</td>
        <td>{{ $credit_card->start_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $credit_card->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td rowspan="2">{{ isset($credit_card->deleted_at) ? $credit_card->deleted_at->format('Y-m-d H:i:s') : '' }}&nbsp;</td>
    </tr>
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td>{{ isset($credit_card->program->title) ? $credit_card->program->title : '' }}&nbsp;</td>
        <td>{{ $credit_card->back }}&nbsp;</td>
        <td>{{ isset($credit_card->apple_pay) ? $apple_pay_map[$credit_card->apple_pay] : '' }}&nbsp;</td>
        <td>{{ $credit_card->stop_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $credit_card->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="12">クレジットカードは存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection
