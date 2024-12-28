@extends('emails.layouts.default')

@section('content')
{{ $user->name }}様
(受付番号:{{ $exchange_request_number }})

GMOポイ活をご利用ありがとうございます。
交換の手続きが実施されましたので、以下の通り、PeXポイントギフトをお送りいたします。

@php
$expire_at = $gift_data->getExpireAt();
@endphp

種類:Pexポイントギフト

ギフトコード:{{ $gift_data->getGiftCode() }}

@if (isset($expire_at))
有効期限:{{ $expire_at->format('Y-m-d H:i:s') }}
@endif
@endsection
