@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、WAONポイントIDをお送りいたします。

WAONポイントID情報
【ギフトID情報】
　・WAONポイントID： {{ number_format($gift_data->getFaceValue()) }}ポイント分
　・ギフトID付登録URL ： {{ $gift_data->getGiftCode2() }}
　・WAONポイントID ： {{ $gift_data->getGiftCode() }}
　・管理番号 ： {{ $gift_data->getManagementCode() }}
　・登録有効期限 ： {{ $gift_data->getExpireAt()->format('Y/m/d') }}


■WAONポイントIDとは（発行元：株式会社NTTカードソリューション）
受け取ったWAONポイントIDを、WAONポイントへ交換することができる電子ギフトです。
WAONポイントは、WAON（電子マネー）に交換（「ポイントチャージ」）することができます。
http://pr.ejoica.jp/c/howto/waonpointid.html
■WAONポイントとは（発行元：イオンリテール株式会社）
WAON（電子マネー）に交換（「ポイントチャージ」）することで、お買物などに利用できるポイントサービスです。
詳細 ： https://www.waon.net/
注意事項 ： https://www.waon.net/point/
■WAON（電子マネー）とは（発行元：イオンリテール株式会社）
WAON加盟店等でのお支払いに利用できる電子マネーです。
詳細 ： https://www.waon.net/about/index.html
注意事項 ： https://www.waon.net/about/shopping/

【WAONポイントIDのご利用方法】
ご利用方法詳細 ： http://pr.ejoica.jp/c/howto/waonpointid.html
1．事前にご自身のWAONカードまたはモバイルWAONの「WAON番号」をご確認ください。
WAON番号の確認方法 ： https://www.waon.net/about/cord/
WAONをお持ちでない場合 ：  https://www.waon.net/card/
※カード番号頭4桁「1000」の「WAON POINT」カードでは、WAONポイントを受け取ることができません。
※未利用のWAONカード、モバイルWAONでは、WAONポイントを受け取ることができません。
一度WAONをご利用（チャージ）してから、登録操作を開始してください。
2．WAONポイントへの交換（登録）操作をおこないます。
「登録有効期限」内に、下記登録サイトまたは「ギフトID付登録URL」にアクセスし、WAONポイントIDとWAON番号の登録操作をおこなってください。
　（「ギフトID付登録URL」にアクセスすると、IDが自動入力されます。）
登録サイト ： https://waonpg.jp/
※誤ったWAON番号をご入力された場合、他の方のWAONに交換されてしまいますのでご注意ください。
※一度交換されたWAONポイントIDの取り消しはできません。
※「登録有効期限」を過ぎるとWAONポイントIDが無効になり、WAONポイントへの交換ができなくなります。
3．「ダウンロード（受取り）」をおこないます。（ポイントダウンロード）
登録完了画面に表示される「ダウンロード（受取り）期限」内に、WAONステーション・WAONネットステーション・モバイルWAON・イオン銀行ATM・Famiポート等の端末でダウンロード（受取り）操作をおこなってください。
※「ダウンロード（受取り）期限」を過ぎるとダウンロード（受取り）できなくなります。
　「ダウンロード（受取り）期限」 ： https://www.waon.net/point/management/exchange/for-etc/index.html
※「JMB WAON」「JMBモバイルWAON」で受け取る場合は、WAONポイントではなく直接WAON（電子マネー）での受け取りとなります。
※ダウンロード（受取り）をおこなう端末によっては、WAONポイントのダウンロード（受取り）とWAON（電子マネー）への交換を同時におこなう場合がございます。
4．WAON（電子マネー）への交換をおこないます。（ポイントチャージ）
ダウンロード（受取り）したWAONポイントは、WAONポイントの有効期限内にWAON（電子マネー）に交換してください。
※WAONポイントはWAON（電子マネー）に交換しないとお買物に使えません。
※有効期限を過ぎたWAONポイントは失効しますので、必ず有効期限までにWAON（電子マネー）に交換してご利用ください。
　WAONポイントの有効期限については、下記のWAON公式サイトにてご確認ください。
　　　https://www.waon.net/point/management/check/
【WAONポイントへの交換に関するご注意事項】
　下記のWAON公式サイトにてご確認ください。
https://www.waon.net/about/point/
【その他のご注意事項】
・WAONポイントIDは、イオン加盟店の店舗等での支払いには直接ご利用できません。
・WAONポイントIDには登録有効期限があります。有効期限を過ぎるとご利用できなくなります。
・WAONポイントに交換操作後、ダウンロード（受取り）期限を過ぎるとダウンロード（受取り）できなくなります。
・WAONポイントにも有効期限があります。有効期限内にWAON（電子マネー）に交換してください。
・WAONポイントIDの登録キャンセル・換金・返金・再発行等はできません。
・WAONポイントIDの紛失、盗難、破損、IDの漏洩等の責任は負いません。

【登録に関するお問い合わせ（株式会社NTTカードソリューション）】
・下記URLよりお問い合わせください。
　　https://atgift.jp/ejoica/pc/index.html
※お問い合せの際は「管理番号」をお知らせください。
@endsection
