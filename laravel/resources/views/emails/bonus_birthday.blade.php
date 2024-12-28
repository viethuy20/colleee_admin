@extends('emails.layouts.default')

@section('title', 'GMOポイ活の誕生日ボーナス')

@section('content')
[{{ $user_name }}]様

お誕生日おめでとうございます！
GMOポイ活から、ささやかなプレゼントです。

☆━━━━━━━━━━━━━━━━☆
▼誕生日ポイント受け取り用URL
{!! config('app.client_url') !!}/users/birthday
☆━━━━━━━━━━━━━━━━☆

上記URLをクリックして頂き、サイトへ正常にアクセスが完了しますと
誕生日ポイントの付与が行われます。

※受け取り用URLの有効期限は、誕生日当日から30日以内となっておりますのでご注意下さい。

@endsection