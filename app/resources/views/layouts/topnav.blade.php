<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'OpenDominion')</title>

    <link rel="author" href="{{ asset('humans.txt') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:title" content="OpenDominion">
    <meta property="og:description" content="OpenDominion is a free online text-based multiplayer strategy game in a medieval fantasy setting. Take control over your dominion with land, buildings, and your army, and compete against other players for a spot in the leaderboards!" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ config('app.url') }}" />
    <meta property="og:image" content="{{ asset('assets/app/images/opendominion.png') }}" />
    <meta property="og:image:width" content="1222" />
    <meta property="og:image:height" content="243" />

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="apple-mobile-web-app-title" content="OpenDominion">
    <meta name="application-name" content="OpenDominion">
    <meta name="theme-color" content="#ffffff" id="meta-theme-color">

    {{-- Apply stored color mode before CSS renders to prevent flash of wrong theme. --}}
    <script>
    (function () {
        var stored = localStorage.getItem('color-mode') || 'auto';
        var bsTheme = (stored === 'classic' || stored === 'dark') ? 'dark'
                    : stored === 'auto'
                      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : 'light';
        document.documentElement.setAttribute('data-bs-theme', bsTheme);
        document.documentElement.setAttribute('data-color-mode', stored);
        if (stored === 'classic') document.documentElement.setAttribute('data-color-scheme', 'classic');
    })();
    </script>

    @include('partials.styles')

    @include('partials.analytics')
</head>
<body class="layout-top-nav bg-body-tertiary">

<div class="app-wrapper">

    <!-- Header -->
    <nav class="app-header navbar navbar-expand-lg bg-body">
        <div class="container">

            <a href="{{ url('') }}" class="navbar-brand">Open<b>Dominion</b></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-collapse" aria-controls="navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Left Menu -->
            <div class="collapse navbar-collapse" id="navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item {{ Route::is('about') ? 'active' : null }}"><a href="{{ route('about') }}" class="nav-link">About</a></li>
                    <li class="nav-item {{ Route::is('valhalla.*') ? 'active' : null }}"><a href="{{ route('valhalla.index') }}" class="nav-link">Valhalla</a></li>
                    <li class="nav-item {{ Route::is('scribes.*') ? 'active' : null }}"><a href="{{ route('scribes.overview') }}" class="nav-link">Scribes</a></li>
                    @include('partials.wiki-nav')
                    @include('partials.staff-nav')
                    <li class="nav-item {{ Route::is('user-agreement') ? 'active' : null }}"><a href="{{ route('user-agreement') }}" class="nav-link">Rules</a></li>
                    @auth
                        @if ($selectorService->hasUserSelectedDominion())
                            <li class="nav-item"><a href="{{ route('dominion.status') }}" class="nav-link"><b>Play</b></a></li>
                        @else
                            <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link"><b>Dashboard</b></a></li>
                        @endif
                    @endauth
                </ul>

                <!-- Navbar Right Menu -->
                <ul class="navbar-nav ms-auto">
                    @include('partials.color-mode-nav')
                    @include('partials.auth-user-nav')
                </ul>
            </div>

        </div>
    </nav>

    <!-- Content -->
    <main class="app-main">
        <div class="app-content">
            <div class="container">

                @include('partials.beta-indicator')

                @hasSection('page-header')
                    <div class="app-content-header py-3">
                        <h3 class="mb-0">
                            @yield('page-header')

                            @hasSection('page-subheader')
                                <small class="text-muted">
                                    @yield('page-subheader')
                                </small>
                            @endif
                        </h3>
                    </div>
                @endif

                @include('partials.alerts')

                @yield('content')

            </div>
        </div>
    </main>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
