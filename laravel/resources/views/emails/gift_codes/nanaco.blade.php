@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、nanacoギフトをお送りいたします。


■nanacoギフトID情報
　・nanacoギフト ：{{ number_format($gift_data->getFaceValue()) }} 円分
　・nanacoギフトID ： {{ $gift_data->getGiftCode() }}
　・管理番号 ： {{ $gift_data->getManagementCode() }}
　・有効期限 ： {{ $gift_data->getExpireAt()->format('Y/m/d') }}
　・ギフトID付登録URL： {{ $gift_data->getGiftCode2() }}


■nanacoギフトとは（発行元：株式会社NTTカードソリューション）
　nanacoギフト及び電子マネー「nanaco」のご利用方法や注意事項等は、下記サイトにてご確認ください。
　http://pr.ejoica.jp/c/howto/nanacogift.html

■nanacoギフトの注意事項
・nanacoギフトは、nanaco加盟店の店舗等での支払いには直接ご利用できません。
・nanacoギフトには、有効期限があります。有効期限を過ぎると、ご利用できなくなりますのでご注意ください。
・登録後のキャンセルや、IDの換金・返金・再発行はできません。
・nanacoギフトの紛失、盗難、破損、IDの漏洩等の責任は負いません。
・nanacoギフトの登録サイトやご利用サイトは、日本国外からのアクセスはできません。

■nanacoギフトのご利用方法
　下記【nanacoカード・nanacoモバイルにチャージして使う】または【オンラインゲームやネットショッピングで使う】のうち、ご希望の方法をご参照ください。
　（ご利用方法詳細：http://pr.ejoica.jp/c/howto/nanacogift.html#use）

【nanacoカード・nanacoモバイルにチャージして使う】
　※事前に「nanacoカード」か「nanacoモバイル」をお手元にご用意ください
　　　nanacoモバイルアプリ バージョン2.00をご利用の方は、nanacoモバイルの操作のみでチャージできます。
　1．≪nanaco公式サイト会員メニュー≫にアクセスしてください
　　※「■nanacoギフトID情報」内の≪ギフトID付登録URL≫よりアクセスすると、ギフトIDが自動入力されます。　
　　≪nanaco公式サイト会員メニュー≫ https://www.nanaco-net.jp/pc/emServlet
　2．『nanaco番号』と、「会員メニュー用パスワード」もしくは「nanacoカード裏面に記載の7桁の番号」を入力し、
　　　［ログイン］ボタンをクリックしてください
　3．［nanacoギフト登録］メニューをクリックしてください
　4．「ご利用約款」をご確認いただき、［ご利用約款に同意の上、登録］ボタンをクリックしてください
　5．画面の案内に従って登録操作をおこなってください
　6．登録完了画面に表示される『受取可能日』を必ずご確認ください
　　※nanacoギフトIDを当日正午12時までに登録完了された場合は翌日の朝6時以降に、
　　　当日正午12時以降に完了された場合は翌々日の朝6時以降に、受け取りが可能になります。
　　　ただしnanaco入会当日に登録が完了した場合は、登録完了時間に関わらず
　　　翌々日の朝6時以降に受け取りが可能になります。
　7．『受取可能日』以降に、所定の方法にて受取り操作をおこなってください
　　※セブン-イレブン、デニーズのレジカウンター、
　　　イトーヨーカドー・ヨークマート・ヨークベニマルのサービスカウンター、
　　　西武・そごうの食品ギフトサロン・商品券売場、セブン銀行のATMで
　　　『残高確認』または『チャージ』をすると、nanacoギフトを受け取ることができます。
　　　受け取り後、全国のnanacoマークのあるお店でご利用いただけます。

【オンラインゲームやネットショッピングで使う】
※「nanacoカード」や「nanacoモバイル」は不要です。
　1．下記URLにアクセスし、nanacoギフト対応のネットショッピングサイトをご確認ください
　　（対応ネットショッピングサイト一覧： http://pr.ejoica.jp/c/howto/nanacogift2.html ）　　
　2．ご希望のサイトのお支払い方法選択画面にて「nanacoギフト」を選択してください
　3．画面の案内に従って「nanacoギフトID」を入力し、お支払いを完了してください
　　※nanacoギフトIDは、残額が0になるまで大切に保管してください。

■nanacoギフトに関してのよくあるご質問は、下記URLよりご確認ください。
　http://pr.ejoica.jp/c-faq/top.html?faq_cat=c_faqservice_nanaco_gift
　※お問い合せの際は「管理番号」をお知らせください。
@endsection
