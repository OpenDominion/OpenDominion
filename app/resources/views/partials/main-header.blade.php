<header class="main-header">

    <!-- Logo -->
    <div class="logo">
        <a href="{{ url('') }}">
            <span class="logo-mini">O<b>D</b></span>
            <span class="logo-lg">Open<b>Dominion</b></span>
        </a>
    </div>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">

        <!-- Sidebar toggle button -->
        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                @include('partials.scribes-nav')
                @include('partials.valhalla-nav')
                @include('partials.staff-nav')
                @include('partials.wiki-nav')
                @include('partials.notification-nav')
                @include('partials.auth-user-nav')
            </ul>
        </div>

    </nav>

</header>
