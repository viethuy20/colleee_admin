@extends('layouts.base')

@section('layout.base.content')
<div class="col-md-10">
        <!-- apply custom style -->
        <div class="page-header" style="margin-top:-30px;padding-bottom:0px;">
            <h1><small>
                @if (Session::has('message'))
                {{ Session::get('message') }}
                @endif
            </small></h1>
        </div>
        @yield('content')
    </div>
@endsection