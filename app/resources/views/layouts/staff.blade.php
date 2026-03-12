<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

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

    @include('partials.analytics')
</head>
<body class="layout-fixed sidebar-expand-lg{{ Auth::user() && Auth::user()->skin == 'skin-classic' ? ' skin-classic bg-body-secondary' : ' bg-body-tertiary' }}">

<div class="app-wrapper">

    @include('partials.main-header')

    @include('partials.main-sidebar')

    <main class="app-main">

        @include('partials.beta-indicator')

        @hasSection('page-header')
            <div class="app-content-header">
                <div class="container-fluid">
                    <h3 class="mb-0">
                        @yield('page-header')

                        @hasSection('page-subheader')
                            <small class="text-muted">
                                @yield('page-subheader')
                            </small>
                        @endif

                        @include('partials.tickers')
                    </h3>
                </div>
            </div>
        @endif

        <div class="app-content">
            <div class="container-fluid">

                @include('partials.alerts')

                <div class="row">

                    <div class="col-md-2">
                        @include('partials.staff.nav')
                    </div>
                    <div class="col-md-10">
                        @yield('content')
                    </div>

                </div>

            </div>
        </div>

    </main>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
