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
<body class="sidebar-expand-lg bg-body-tertiary">

<div class="app-wrapper">

    @include('partials.main-header')

    @include('partials.main-sidebar')

    <main class="app-main">

        @include('partials.beta-indicator')
        @include('partials.protection-indicator')

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

                @include('partials.resources-overview')

                @yield('content')

            </div>
        </div>

    </main>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
