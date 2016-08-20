<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

    <title>@yield('title', 'OpenDominion')</title>

    {{-- Vendor styles --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/metisMenu/css/metisMenu.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/sb-admin-2/css/sb-admin-2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/rpg-awesome/css/rpg-awesome.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

    {{-- Page specific styles --}}
    @stack('page-styles')

    {{-- Page specific inline styles --}}
    @stack('inline-styles')

    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<div id="wrapper">

    @include('partials.navbar')

    <div id="page-wrapper">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    @yield('page-header')
                </h1>
            </div>
        </div>

        @include('partials.alerts')

        @if (($dominion = Request::route()->getParameter('dominion')) && Request::is('dominion/*') && !Request::is('dominion/*/status'))
            @include('partials.resources-overview')
        @endif

        @yield('content')

    </div>

</div>

{{-- Vendor scripts --}}
<script type="text/javascript" src="{{ asset('assets/vendor/jquery/js/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/metisMenu/js/metisMenu.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/sb-admin-2/js/sb-admin-2.js') }}"></script>

{{-- app.js --}}

{{-- Page specific scripts --}}
@stack('page-scripts')

{{-- Page specific inline scripts --}}
@stack('inline-scripts')

</body>
</html>
