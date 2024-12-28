@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、Google Play ギフトコードをお送りいたします。

■ Google Play ギフトコード
コード: {{ $gift_data->getGiftCode() }}
金額 : {{ $gift_data->getFaceValue() }}円

■ Google Play ギフトコードとは
アプリやゲームをはじめ数百万のアイテムが揃った Google Play なら欲しいものが必ず見つかります。Google Play ギフトコードを手に、無限に楽しめる世界を探検しましょう。人気のゲームや毎日の生活に欠かせないアプリを、手数料なし、有効期限なし、クレジット カードなしで気軽に手に入れることができ、大切な人へのギフトとして最適です。もちろん、自分へのご褒美としても。

■ Google Play ギフトコードのご利用方法
日本の Google Play ストアでのみ使用できます。利用規約が適用されます。
Google Play ギフトコードは、Android(TM) の公式アプリストアである Google Play ストアで、アプリやゲームなどの購入に使用できます。
利用するには、Play ストア アプリまたは play.google.com でコードを入力してください。
このギフトコードのコードは Google Play および YouTube(TM) でのみご利用いただけます。
コードに関するその他の要求はすべて詐欺の可能性があります。詳しくは、play.google.com/giftcardscam をご覧ください。

■ 利用規約
このギフトコードは グーグル・ペイメント合同会社（「GPJ」）が発行するものです。
利用規約およびプライバシーポリシーは play.google.com/jp-card-terms をご覧ください。
13歳以上の日本の居住者にのみ有効です。
ご利用には Google(TM) ペイメントのアカウントとインターネットアクセスが必要です。
利用するには、Play ストア アプリまたは play.google.com でコードを入力してください。
このギフトコードのコードは、Google Play および YouTube でのみご利用いただけます。
コードに関するその他の要求はすべて詐欺の可能性があります。
詳しくは、play.google.com/giftcardscam をご覧ください。
デバイス、定期購読のご購入には使用できないことがあります。
その他の制限が適用される場合があります。
手数料や使用期限はありません。
法律上必要な場合を除き返金や他のカードとの交換はできません。
クレジットアカウントにはチャージできません。
カードの金額を補充または返金することはできません。
Google Play 以外のアカウントにチャージすることはできません。
転売、交換、譲渡することはできません。
カードの紛失盗難等についてはお客様の責任となります。
残高確認等のお問い合わせは、 support.google.com/googleplay/go/cardhelp、Google Inc.,1600 Amphitheatre Parkway, Mountain View, CA 94043までお願いします。 
Google、Android、Google Play、YouTube は Google LLC の商標です。
@endsection
