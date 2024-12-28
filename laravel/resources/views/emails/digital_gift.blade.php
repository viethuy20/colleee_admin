@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
以下の通り、デジタルギフト {{ $code_name }}のポイント交換URLをお送りいたします。
 

--------------------------------------------------------
ポイント交換URL ：{{ $url }}
--------------------------------------------------------

@endsection

@section('footer')
*本キャンペーンはGMO NIKKO株式会社による提供です。
  本キャンペーンについてのお問い合わせはデジタルギフトではお受けしておりません。
　お問い合わせ・よくある質問【https://colleee.net/support/】 までお願いいたします。
@endsection
