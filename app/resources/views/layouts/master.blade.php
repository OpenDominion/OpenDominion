<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>@yield('title', 'OpenDominion')</title>

    <link rel="author" href="{{ asset('humans.txt') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="apple-mobile-web-app-title" content="OpenDominion">
    <meta name="application-name" content="OpenDominion">
    @if (Auth::user() && Auth::user()->skin == 'skin-classic')
        <meta name="theme-color" content="#000000">
        <style type="text/css">
            :root {
                background: #000000;
                color-scheme: dark;
            }
        </style>
    @else
        <meta name="theme-color" content="#ffffff">
    @endif

    @include('partials.styles')

    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    @include('partials.analytics')
</head>
<body class="hold-transition {{ Auth::user() && Auth::user()->skin ? Auth::user()->skin : 'skin-blue' }}  sidebar-mini">

<div class="wrapper">

    @include('partials.main-header')

    @include('partials.main-sidebar')

    <div class="content-wrapper">
        @include('partials.beta-indicator')
        @include('partials.protection-indicator')

        @hasSection('page-header')
            <div class="content-header">
                <h1>
                    @yield('page-header')

                    @hasSection('page-subheader')
                        <small>
                            @yield('page-subheader')
                        </small>
                    @endif
                    @include('partials.tickers')
                </h1>
            </div>
        @endif

        <section class="content">

            @include('partials.alerts')

            @include('partials.resources-overview')

            @yield('content')

        </section>

    </div>

    @include('partials.main-footer')

    @include('partials.control-sidebar')

</div>

@include('partials.scripts')

</body>
</html>
