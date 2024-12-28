<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title')</title>
        <link href="{{ url('/favicon.ico') }}" type="image/x-icon" rel="icon" />
        <link href="{{ url('/favicon.ico') }}" type="image/x-icon" rel="shortcut icon" />
        {!! Tag::style('/css/bootstrap.min.css') !!}
        {!! Tag::style('/css/bootstrap-theme.min.css') !!}
        {!! Tag::style('/css/custom.css?20230609') !!}
        {!! Tag::style('/jquery-ui-1.12.1/jquery-ui.min.css') !!}
        {!! Tag::script('/js/jquery-3.3.1.min.js', ['type' => 'text/javascript']) !!}
        {!! Tag::script('/js/bootstrap.min.js', ['type' => 'text/javascript']) !!}
        {!! Tag::script('/jquery-ui-1.12.1/jquery-ui.min.js', ['type' => 'text/javascript']) !!}
        {!! Tag::script('/js/tinymce/tinymce.min.js', ['type' => 'text/javascript']) !!}
        {!! Tag::script('/js/chart.js', ['type' => 'text/javascript']) !!}
        {!! Tag::script('/js/kpi.js', ['type' => 'text/javascript']) !!}
        <style type="text/css">
        <!--
        body {
            padding-top: 120px;
            padding-bottom: 0px;
        }
        -->
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <script type="text/javascript">
        <!--
        $(function() {
            $('.LockForm').submit(function(event) {
                var self = this;
                $(":submit", self).prop("disabled", true);
                setTimeout(function() {
                    $(":submit", self).prop("disabled", false);
                }, 3000);
            });
        });
        // -->
        </script>
        @yield('head.load')
    </head>

    <body>
        @yield('layout.base.content')
    </body>
</html>
