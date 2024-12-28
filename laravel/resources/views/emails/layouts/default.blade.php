
@yield('content')

本メールに関するお問い合わせ先
━━━━━━━━━━━━━━━━
GMOポイ活
{!! config('app.client_url') !!}

■お問い合わせ・よくある質問
{!! config('app.client_url') !!}/support/

■運営会社：GMO NIKKO株式会社
{!! config('url.gmo_nikko') !!}
━━━━━━━━━━━━━━━━
@hasSection ('footer')
@yield('footer')
@endif
