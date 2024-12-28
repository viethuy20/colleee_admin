@extends('emails.layouts.default')

@section('title', 'GMOポイ活メールアドレス再設定URLのご連絡')

@section('content')
下記のURLからメールアドレス変更作業を行ってください。

{!! config('app.client_url') !!}/reminders/email/{{ $email_token_id }}

@endsection