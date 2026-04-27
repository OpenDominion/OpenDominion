<nav class="app-header navbar navbar-expand py-0">
    <div class="container-fluid px-0">

        <!-- Sidebar toggle -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fa fa-bars"></i>
                </a>
            </li>
        </ul>

        <!-- Right side: tickers + menu -->
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                @include('partials.tickers')
            </li>
            @include('partials.links-nav')
            @include('partials.color-mode-nav')
            @include('partials.notification-nav')
            @include('partials.auth-user-nav')
        </ul>

    </div>
</nav>
