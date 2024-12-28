@extends('emails.layouts.default')

@section('title', $title)

@section('content')

ID：{{ $email }}
PW：{{ $password }}

@endsection

