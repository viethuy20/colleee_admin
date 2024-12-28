@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、EdyギフトIDをお送りいたします。

■EdyギフトID情報
　・EdyギフトID ： {{ number_format($gift_data->getFaceValue()) }}円分
　・ギフトID付登録URL ： {{ $gift_data->getGiftCode2() }}
　・EdyギフトID ： {{ $gift_data->getGiftCode() }}
　・管理番号 ： {{ $gift_data->getManagementCode() }}
　・有効期限 ： {{ $gift_data->getExpireAt()->format('Y/m/d') }}

■EdyギフトIDとは（発行元：(株)NTTカードソリューション）
　EdyギフトID及び電子マネー「楽天Edy」のご利用方法や注意事項等は、下記サイトにてご確認ください。
　http://pr.ejoica.jp/c/howto/edygiftid.html

■EdyギフトIDの注意事項
　・EdyギフトIDは、楽天Edy加盟店の店舗等での支払いには直接ご利用できません。
　・EdyギフトIDには、有効期限があります。有効期限を過ぎると、ご利用できなくなりますのでご注意ください。
　・ギフトID登録後、60日以内にEdyを受け取ってください。
　　受取り期限を過ぎると、Edyの受け取りができなくなりますのでご注意ください。
　・Edy残高の合計が50,000円を超えると、Edyを受け取ることができません。
　　受け取るEdyの額と残高の合計が50,000円以下となるよう、事前にご確認ください。
　・登録後のキャンセルや、IDの換金　・返金　・再発行はできません。
　・EdyギフトIDの紛失、盗難、破損、IDの漏洩等の責任は負いません。
　・EdyギフトIDの登録サイトは、日本国外からのアクセスはできません。

■EdyギフトIDのご利用方法
　（ご利用方法詳細：http://pr.ejoica.jp/c/howto/edygiftid.html#use）
　※事前に「Edyカード」か「楽天Edyアプリ」をお手元にご用意ください。
　1．登録サイトへアクセスします
　　　https://giftid.edy.jp/
　2．ギフトIDとお持ちのEdy番号を入力します
　3．利用者規約をご一読の上チェックボックスにチェックを入れ、「入力情報を送信」ボタンを押してください
　4．画面の案内に従い登録操作を完了します
　5．専用端末で受取操作をし、登録したEdyギフトIDの金額をEdyにチャージします
　　　受取り方法は楽天Edyサイト（ https://edy.rakuten.co.jp/howto/card/edy_rpointcard/gift/ ）をご参照ください。


■EdyギフトIDに関してのよくあるご質問(登録方法など)は、下記のURLをご覧下さい。
　http://pr.ejoica.jp/c-faq/top.html?faq_cat=c_faqservice_edy_gift
　※お問い合せの際は「管理番号」をお知らせください。
@endsection
