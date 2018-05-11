<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>@yield('title', 'OpenDominion')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="apple-mobile-web-app-title" content="OpenDominion">
    <meta name="application-name" content="OpenDominion">
    <meta name="theme-color" content="#ffffff">

    @include('partials.styles')

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition skin-blue layout-top-nav">
{!! Analytics::render() !!}

<div class="wrapper">

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="container">

                <!-- Navbar Header -->
                <div class="navbar-header">
                    <a href="{{ url('') }}" class="navbar-brand collapse navbar-collapse">Open<b>Dominion</b></a>
                    <button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>

                <!-- Navbar Left Menu -->
                <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="{{ Route::is('home') ? 'active' : null }}"><a href="{{ route('home') }}">Home</a></li>
                        <li class="{{ Route::is('valhalla.*') ? 'active' : null }}"><a href="{{ route('valhalla.index') }}">Valhalla</a></li>
                        @auth
                            @if ($selectorService->hasUserSelectedDominion())
                                <li><a href="{{ route('dominion.status') }}">Play</a></li>
                            @else
                                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            @endif
                        @endauth
                    </ul>
                </div>

                <!-- Navbar Right Menu -->
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        @include('partials.auth-user-nav')
                    </ul>
                </div>

            </div>
        </nav>
    </header>

    <!-- Content -->
    <div class="content-wrapper">
        @include('partials.beta-indicator')

        <div class="container">

            @hasSection('page-header')
                <div class="content-header">
                    <h1>
                        @yield('page-header')

                        @hasSection('page-subheader')
                            <small>
                                @yield('page-subheader')
                            </small>
                        @endif
                    </h1>
                </div>
            @endif

            <div class="content">

                @include('partials.alerts')

                @yield('content')

            </div>

        </div>
    </div>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
