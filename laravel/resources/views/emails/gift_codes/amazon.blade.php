@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、Amazonギフトカードをお送りいたします。
 
■Amazonギフトカード
（＊Amazonギフトカードの再発行は致しかねます。このメッセージをプリントアウトすることをお勧めいたします。）
（＊入力間違い防止の為、メールの場合はコピー＆ペーストをお勧めいたします。）
--------------------------------------------------------
ギフトカード番号：{{ $gift_data->getGiftCode() }}（＊ハイフンも含みます）
金額：　　　{{ number_format($gift_data->getFaceValue()) }}円
有効期限：　{{ $gift_data->getExpireAt()->format('Y/m/d') }}
--------------------------------------------------------
■Amazonギフトカードのご利用方法
Amazonギフトカードをご利用いただくには、最初にアカウントにギフトカードを登録します。
1. 　www.amazon.co.jp/giftcard/use にアクセスする。
2.　サインインする。
3.　ギフトカード番号を入力し、「アカウントに登録する」をクリックする。

■Amazonギフトカード　細則
Amazon Gift Cards Japan合同会社 (「当社」) が発行するAmazonギフトカード (「ギフトカード」)のご利用には、
http://www.amazon.co.jp (PC・モバイルを含み「アマゾンサイト」) のアカウント作成が必要です。
ギフトカードは、アマゾンサイトまたは一部のAmazon Pay加盟店でご利用できますが、他のギフトカードの購入又は一部の会費の支払等には利用できません。
このギフトカードの有効期限は発行日から10年間です。ギフトカードの換金・返金等はできません。
当社及び当社の関連会社は、ギフトカードの紛失・盗難等について一切責任を負いません。
ギフトカードに関するお問合せは、カスタマーサービス(東京都目黒区下目黒1-8-1, https://amazon.co.jp/contact-us )までお願いします。
詳細は、細則( www.amazon.co.jp/giftcard/tc )をご覧下さい。
@endsection

@section('footer')
*本キャンペーンはGMO NIKKO株式会社による提供です。
  本キャンペーンについてのお問い合わせはAmazonではお受けしておりません。
　お問い合わせ・よくある質問【https://colleee.net/support/】 までお願いいたします。
*Amazon、Amazon.co.jpおよびそれらのロゴはAmazon.com, Inc.またはその関連会社の商標です。
@endsection
