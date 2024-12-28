@extends('layouts.master')

@section('title', 'プログラム管理')

@section('head.load')
<script type="text/javascript"><!--
$(function() {
    tinyMCE.init({
        mode: "textareas",
        selector:"#CreditCardCampaign",
        language: "ja",
        toolbar: "forecolor link bullist image code",
        plugins: "textcolor link lists image imagetools code autoresize",
        fullpage_default_doctype: "",
        fullpage_default_encoding: "UTF-8",
        menubar: false,
        statusbar: false,
        cleanup : false,
        
        force_br_newlines : true,
        force_p_newlines : false,
        forced_root_block : '',
        
        document_base_url : "{{ config('app.client_url') }}",
        convert_urls : false,
        file_picker_callback : function(callback, value, meta) {
            imageFilePicker(callback, value, meta);
        },
        imagetools_toolbar: "rotateleft rotateright | flipv fliph | editimage imageoptions"
    });

    // 画像URL処理
    $('.fileUrl').on('change', function(event) {
        var img = $('#' + $(this).attr('forImg'));
        // 読み込んだデータをimgに設定
        img.attr('src', $(this).val());
        // 表示
        img.show();
    });

    // 年会費
    $("#CreditCardAnnualFree").on('change', function(event) {
        if ($(this).prop('checked')) {
            $("#CreditCardAnnualDetail").val('');
            $("#CreditCardAnnualDetail").prop("disabled", true);
        } else {
            $("#CreditCardAnnualDetail").prop("disabled", false);
        }
    })
});
//-->
</script>
@endsection

@section('menu')
<li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
<li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
@endsection

@section('content')

@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

@include('elements.attachment_select', ['img_ids_id' => 'ProgramImgIds', 'parent_type' => 'program', 'parent_id' => $credit_card['program_id']])

{{ Tag::formOpen(['url' => route('credit_cards.store'), 'method' => 'post', 'class' => 'LockForm']) }}
@csrf    
<fieldset>
        @if (isset($credit_card['id']))
        <legend>クレジットカード更新</legend>
        @else
        <legend>クレジットカード作成</legend>
        @endif

        <div class="form-group">
            <label>プログラムID</label><br />
            {{ $credit_card['program_id'] }}<br />
            {{ Tag::formHidden('program_id', $credit_card['program_id']) }}
        </div>

        <div class="form-group">
            <label for="CreditCardTitle">タイトル</label><br />
            {{ Tag::formText('title', old('title', $credit_card['title'] ?? ''), ['class' => 'form-control', 'id' => 'CreditCardTitle']) }}<br />
        </div>

        <div class="form-group">
            <label for="CreditCardImgUrl">画像URL</label><br />
            @php
            $img_id = 'CreditCardImg';
            $img_url = old('img_url', $credit_card['img_url'] ?? null);
            @endphp
            @if (isset($img_url))
            {!! Tag::image($img_url, 'img', ['id' => $img_id, 'width' => '150px']) !!}
            @else
            <img id="{{ $img_id }}" alt="img" width="150px" style="display:none" />
            @endif
            {{ Tag::formText('img_url', $img_url, ['class' => 'form-control fileUrl', 'maxlength' => '256', 'id' => 'CreditCardImgUrl', 'forImg' => $img_id]) }}<br />
            <input type="button" onclick="openImageDialog('{{ $img_id }}', 'CreditCardImgUrl');" value="参照" /><br />
        </div>

        <div class="form-group">
            <label for="CreditCardDetail">詳細</label><br />
            {{ Tag::formTextarea('detail', old('detail', $credit_card['detail'] ?? ''), ['class' => 'form-control', 'rows' => 5, 'id' => 'CreditCardDetail']) }}<br />
        </div>

        <div class="form-group"><div class="form-inline">
            <label for="CreditCardPoint">共通ポイント情報</label><br />
            <div class="form-group"><div>
                @php
                // 共通ポイント
                $point_type_map = config('map.credit_card_point_type');
                @endphp
                @foreach ($point_type_map as $type => $label)
                {{ $label }}<br />
                {{ Tag::formText("point_map[$type]", $credit_card['point_map'][$type] ?? '', ['class' => 'form-control', 'size' => 100]) }}<br />
                @endforeach
            </div></div>
        </div></div>

        <div class="form-group">
            <label for="CreditCardCampaign">キャンペーン情報</label><br />
            {{ Tag::formTextarea('campaign', old('campaign', $credit_card['campaign'] ?? ''), ['class' => 'form-control', 'rows' => 5, 'id' => 'CreditCardCampaign']) }}<br />
        </div>

        <p><b>ブランド</b></p>
        <div class="checkbox">
            @php
            $brand = old('brand', $credit_card['brand']);
            $brand_map = config('map.credit_card_brand');
            @endphp
            @foreach($brand_map as $key => $label)
            @php
            $check_box_id = 'brand'.$key;
            @endphp
            <label for="{{ $check_box_id }}" class="selected">
                {{ Tag::formCheckbox('brand['.$key.']', $key, in_array($key, $brand), ['id' => $check_box_id]) }}{{ $label }}
            </label>
            @endforeach
        </div>

        <div class="form-group"><div class="form-inline">
            <label for="CreditCardAnnual">年会費</label><br />
            <div class="form-group">
                {{ Tag::formCheckbox('annual_free', 1, old('annual_free', $credit_card['annual_free'] ?? null), ['id' => 'CreditCardAnnualFree']) }}永年無料
            </div>
            <div class="form-group">
                {{ Tag::formText('annual_detail', old('annual_detail', $credit_card['annual_detail'] ?? null), ['class' => 'form-control', 'id' => 'CreditCardAnnualDetail', 'size' => 100, 'disabled' => $credit_card['annual_free'] == 1 ? 'disabled' : null]) }}
            </div>
        </div></div>

        <div class="form-group">
            <label for="CreditCardBack">ポイント還元率</label><br />
            {{ Tag::formText('back', old('back', $credit_card['back'] ?? ''), ['class' => 'form-control', 'id' => 'CreditCardBack']) }}
        </div>

        <p><b>電子マネー</b></p>
        <div class="checkbox">
            @php
            $emoney = old('emoney', $credit_card['emoney']);
            $emoney_map = config('map.credit_card_emoney');
            @endphp
            @foreach($emoney_map as $key => $label)
            @php
            $check_box_id = 'emoney'.$key;
            @endphp
            <label for="{{ $check_box_id }}" class="selected">
                {{ Tag::formCheckbox('emoney['.$key.']', $key, in_array($key, $emoney), ['id' => $check_box_id]) }}{{ $label }}
            </label>
            @endforeach
        </div>

        <div class="form-group"><div class="form-inline">
            <label for="CreditCardEtc">ETC</label><br />
            <div class="form-group">
                {{ Tag::formCheckbox('etc', 1, old('etc', $credit_card['etc'] ?? null), ['id' => 'CreditCardEtc']) }}ETC付き
            </div>
            <div class="form-group">
                {{ Tag::formText('etc_detail', old('etc_detail', $credit_card['etc_detail'] ?? null), ['class' => 'form-control', 'id' => 'CreditCardEtcDetail', 'size' => 100]) }}
            </div>
        </div></div>

        <div class="form-group">
            <label for="CreditCardApplePay">ApplePay</label><br />
            {{ Tag::formSelect('apple_pay', config('map.credit_card_apple_pay'), $credit_card['apple_pay'] ?? '', ['class' => 'form-control', 'id' => 'CreditCardApplePay']) }}<br />
        </div>

        <p><b>付帯保険</b></p>
        <div class="checkbox">
            @php
            $insurance = old('insurance', $credit_card['insurance']);
            $insurance_map = config('map.credit_card_insurance');
            @endphp
            @foreach($insurance_map as $key => $label)
            @php
            $check_box_id = 'insurance'.$key;
            @endphp
            <label for="{{ $check_box_id }}" class="selected">
                {{ Tag::formCheckbox('insurance['.$key.']', $key, in_array($key, $insurance), ['id' => $check_box_id]) }}{{ $label }}
            </label>
            @endforeach
        </div>

        <div class="form-group">
            <label for="CreditCardRecommendShops">おすすめショップ情報</label><br />
            <div class="form-inline">
                @for ($i = 0; $i < 4; $i++)
                {{ Tag::formText('recommend_shop[]', $credit_card['recommend_shop'][$i] ?? '', ['class' => 'form-control', 'id' => '', 'size' => 100]) }}<br />
                @endfor
                <br />
            </div>
        </div>

        <div class="form-group">
            <label for="CreditCardStartAt">掲載期間</label>
            <div class="form-inline">
                {{ Tag::formText('start_at', old('start_at', $credit_card['start_at'] ?? ''), ['class' => 'form-control', 'id' => 'CreditCardStartAt']) }}
                ～
                {{ Tag::formText('stop_at', old('stop_at', $credit_card['stop_at'] ?? ''), ['class' => 'form-control']) }}
            </div>
        </div>

        <div class="form-group">{{ Tag::formSubmit('送信', ['class' => 'btn btn-default']) }}</div>
    </fieldset>
{{ Tag::formClose() }}
@endsection
