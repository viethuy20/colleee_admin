@php
    $menuItems = [
        'banks.index' => '金融機関振込申し込み一覧',
        'gift_codes.index' => 'ギフトコード申し込み一覧',
        'dot_money.index' => 'ドットマネー申し込み一覧',
        'd_point.index' => 'Dポイント申し込み一覧',
        'line_pay.index' => 'LINE Pay申し込み一覧',
        'paypay.index' => 'PayPay申し込み一覧',
        'digital_gift_paypal.index' => 'デジタルギフトPayPal申し込み一覧',
        'jalmile.index' => 'JALマイル申し込み一覧',
        'kdol.index' => 'KDOL申し込み一覧',
        'exchange_requests.import' => '交換申し込み一括更新',
        'exchange_infos.index' => '交換先一覧',
    ];
@endphp

@foreach ($menuItems as $route => $label)
    <li class="{{ $currentRoute == $route ? 'active' : '' }}">
        {{ Tag::link(route($route), $label) }}
    </li>
@endforeach