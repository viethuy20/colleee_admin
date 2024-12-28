@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、Apple Gift Cardをお送りいたします。

■Apple Gift Card
コード ：{{ $gift_data->getGiftCode() }}
金額：{{ number_format($gift_data->getFaceValue()) }}円
有効期限：有効期限はありません
オンラインでのご利用URL : {{ $gift_data->getGiftCode2() }}

■Apple Gift Cardのご利用方法
Apple Gift Cardの使い方は2通りあります。
Apple StoreでApple製品の購入に使用するには、ギフトカードを利用する前に、このメールをApple Storeにお持ちください。
オンラインでの購入は、apple.com/redeem にアクセスして、Appleアカウントの残高にカード金額をチャージしてください。

ギフトカード詐欺にご注意ください。コードを共有しないでください。

■Apple Gift Cardの利用規約
日本国内におけるAppleからの購入のみに利用できます。
Apple Gift Cardに関するお問い合わせは、support.apple.com/giftcard をご覧いただくか、0120-277-535までお電話ください。
Apple製品取扱店では利用できません。また、法律で定められている場合を除き、現金との引換、転売、払い戻し、または商品交換はできません。
Appleは、Apple Gift Cardの不正使用に対して責任を負いません。
諸条件が適用されます。apple.com/jp/go/legal/gc をご覧ください。これらの条件が法的権利に影響を及ぼすことはありません。
Apple Gift Cardの有効期限はありません。発行：iTunes株式会社
(c) {{ \Carbon\Carbon::now()->year }} iTunes K.K. All rights reserved.
@endsection
