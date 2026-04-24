<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'OpenDominion')</title>

    <meta name="theme-color" content="#ffffff" id="meta-theme-color">

    {{-- Apply stored color mode before CSS renders to prevent flash of wrong theme. --}}
    <script>
    (function () {
        var stored = localStorage.getItem('color-mode') || 'auto';
        var darkModes = ['classic', 'dark', 'terminal'];
        var bsTheme = darkModes.indexOf(stored) !== -1 ? 'dark'
                    : stored === 'auto'
                      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : 'light';
        var customSchemes = ['classic', 'parchment', 'terminal'];
        document.documentElement.setAttribute('data-bs-theme', bsTheme);
        document.documentElement.setAttribute('data-color-mode', stored);
        if (customSchemes.indexOf(stored) !== -1) document.documentElement.setAttribute('data-color-scheme', stored);
    })();
    </script>

    @include('partials.styles')
</head>
<body class="layout-top-nav bg-body-tertiary">

<div class="app-wrapper">

    <!-- Header -->
    <nav class="app-header navbar navbar-expand-lg bg-body">
        <div class="container">
            <a href="{{ url('') }}" class="navbar-brand">Open<b>Dominion</b></a>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item active"><a href="{{ route('home') }}" class="nav-link">Error 503</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="app-main">
        <div class="app-content">
            <div class="container">

                <div class="row">
                    <div class="col-sm-8 offset-sm-2">

                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <span class="card-title">Status 503: Service Unavailable</span>
                            </div>
                            <div class="card-body">
                                <p>
                                    OpenDominion is currently down for maintenance.
                                </p>
                                <p>
                                    <dl>
                                        <dt>Message:</dt>
                                        <dd>{{ $exception->getMessage() }}</dd>
                                    </dl>
                                </p>
                            </div>
                        </div>

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
