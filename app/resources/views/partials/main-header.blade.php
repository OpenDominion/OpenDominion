<nav class="app-header navbar navbar-expand bg-body" data-bs-theme="dark">
    <div class="container-fluid">

        <!-- Sidebar toggle -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fa fa-bars"></i>
                </a>
            </li>
        </ul>

        <!-- Brand -->
        <a href="{{ url('') }}" class="navbar-brand ms-2">
            Open<b>Dominion</b>
        </a>

        <!-- Navbar Right Menu -->
        <ul class="navbar-nav ms-auto">
            @include('partials.scribes-nav')
            @include('partials.valhalla-nav')
            @include('partials.staff-nav')
            @include('partials.wiki-nav')
            @include('partials.notification-nav')
            @include('partials.auth-user-nav')
        </ul>

    </div>
</nav>
