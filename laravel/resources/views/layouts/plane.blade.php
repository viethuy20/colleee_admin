@extends('layouts.base')

@section('layout.base.content')
<header class="container-fluid bHead">
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#gnavi">
                <span class="sr-only">メニュー</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div id="gnavi" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                @yield('layout.menu')
            </ul>
        </div>
    </nav>
</header>
<div class="container-fluid">
    @yield('layout.content')
</div>
<footer>
    <div class="container">&copy; GMO NIKKO, Inc. All Rights Reserved.</div>
</footer>
@endsection