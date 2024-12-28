@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様

この度は、GMOポイ活をご利用いただきありがとうございます。
交換の手続きが実施されましたので、以下の通り、Pontaポイント コードをお送りいたします。

■Pontaポイント コード情報
（※Pontaポイント コードの再発行はいたしかねます。このメッセージをプリントアウトすることをお勧めいたします。）
（※入力間違い防止の為、メールの場合はコピー＆ペーストをお勧めいたします。）
-----------------------------------------------
Pontaポイント コード : {{ $gift_data->getGiftCode() }}
ポイント数 : {{ number_format($gift_data->getFaceValue()) }}ポイント
管理番号 : {{ $gift_data->getManagementCode() }}
有効期限 : {{ $gift_data->getExpireAt()->format('Y/m/d') }}
-----------------------------------------------

■Pontaポイント コードとは（発行元：株式会社NTTカードソリューション）
Pontaポイント コードは、Ponta会員IDにPontaポイントを加算できるサービスです。
https://atgift.jp/user/item/pontapointcode/

■Pontaポイント コードのご利用方法
下記URLにて利用手順をご確認のうえ、ご利用ください。
https://atgift.jp/user/item/pontapointcode/?sid=section3

1. Pontaポイントの加算登録をおこないます。
「有効期限」内に、下記登録サイトまたは「ギフトID付登録URL」にアクセスし、Pontaポイント コードの加算登録をおこなってください。
登録サイト : https://ejoica.jp/pontapoint/
※Pontaポイントの加算登録には、PontaWeb会員登録が必要です。
※PontaWeb会員登録の際には、Ponta会員IDとリクルートIDのご登録が必要です。
※PontaWeb会員登録がお済みでない方は、登録サイトから会員登録をお願いいたします。

2. Pontaポイントが加算登録されます。
登録サイト「STEP2 内容確認」画面に表示されるPonta会員IDが、ポイント加算対象となります。
※Pontaポイント加算登録後のキャンセルはできません。
※Pontaポイントは加算登録後即時加算されます。
※諸事情により、Pontaポイントの加算にお時間をいただく場合がございます。予めご了承ください。

■Pontaポイント コードの注意事項
・Pontaポイント コードには有効期限があります。有効期限を過ぎるとご利用できなくなります。
・Pontaポイント コードの登録キャンセル・換金・返金・再発行等はできません。
・Pontaポイント コードの紛失、盗難、破損、IDの漏洩等の責任は負いません。
・Pontaポイント コードの登録サイトは、日本国外からのアクセスはできません。
・その他注意事項は「利用規約」（ https://atgift.jp/user/item/pontapointcode/use-rule/ ）をご確認ください。

■Pontaポイント コードに関するお問い合わせ（株式会社NTTカードソリューション）
Pontaポイント コードに関してのよくあるご質問（登録方法など）は、下記のURLをご覧ください。
https://atgift.jp/user/c-faq/pontapointcode/
※お問い合わせの際は「管理番号」をお知らせください。
@endsection

@section('footer')

「Ponta」は、株式会社ロイヤリティ マーケティングの登録商標です。
「Pontaポイント コード」は、株式会社ロイヤリティ マーケティングとの発行許諾契約により、株式会社NTTカードソリューションが発行するサービスです。
@endsection
