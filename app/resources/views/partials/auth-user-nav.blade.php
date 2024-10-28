@auth
    <!-- User Account Menu -->
    <li class="dropdown user user-menu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <img src="{{ Auth::user()->getAvatarUrl() }}" class="user-image" alt="{{ Auth::user()->display_name }}">
            <span class="hidden-xs">{{ Auth::user()->display_name }}</span>
        </a>
        <ul class="dropdown-menu">
            <li class="user-header">
                <img src="{{ Auth::user()->getAvatarUrl() }}" class="img-circle" alt="{{ Auth::user()->display_name }}">
                <p>
                    {{ Auth::user()->display_name }}
                    <small>Playing since {{ Auth::user()->created_at->toFormattedDateString() }}</small>
                </p>
            </li>
            <li class="user-body">
                <div class="row">
                    <div class="col-xs-4 text-center">
                        <a href="{{ route('valhalla.user', Auth::user()->id) }}">Profile</a>
                    </div>
                    <div class="col-xs-4 text-center">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </div>
                    <div class="col-xs-4 text-center">
                        <a href="{{ route('settings') }}">Settings</a>
                    </div>
                </div>
            </li>
            <li class="user-footer">
                <div class="pull-right">
                    <form action="{{ route('auth.logout') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-sign-out fa-fw"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </li>
@else
    <li class="{{ Route::is('auth.register') ? 'active' : null }}"><a href="{{ route('auth.register') }}">Register</a></li>
    <li class="{{ Route::is('auth.login') ? 'active' : null }}"><a href="{{ route('auth.login') }}">Login</a></li>
@endauth
