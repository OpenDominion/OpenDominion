<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">

    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Togle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a href="{{ route('home') }}" class="navbar-brand">OpenDominion</a>
    </div>

    <ul class="nav navbar-top-links navbar-right">

        @if (Auth::check())
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-user fa-fw"></i>
                    <span>{{ Auth::user()->display_name }}</span>
                    <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-user">
                    <li>
                        <a href="#"><i class="fa fa-user fa-fw"></i> User Profile</a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="{{ route('auth.logout') }}"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                    </li>
                </ul>
            </li>
        @else
            <li>
                <a href="{{ route('auth.login') }}">Login</a>
            </li>
            <li>
                <a href="{{ route('auth.register') }}">Register</a>
            </li>
        @endif

    </ul>

    @include('partials.sidebar')

</nav>
