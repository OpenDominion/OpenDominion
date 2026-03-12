@auth
    <!-- User Account Menu -->
    <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
            <img src="{{ Auth::user()->getAvatarUrl() }}" class="user-image rounded-circle shadow" alt="{{ Auth::user()->display_name }}">
            <span class="d-none d-md-inline">{{ Auth::user()->display_name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
            <li class="user-header">
                <img src="{{ Auth::user()->getAvatarUrl() }}" class="rounded-circle shadow" alt="{{ Auth::user()->display_name }}">
                <p>
                    {{ Auth::user()->display_name }}
                    <small>Playing since {{ Auth::user()->created_at->toFormattedDateString() }}</small>
                </p>
            </li>
            <li class="user-body">
                <div class="row">
                    <div class="col-4 text-center">
                        <a href="{{ route('valhalla.user', Auth::user()->id) }}">Profile</a>
                    </div>
                    <div class="col-4 text-center">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </div>
                    <div class="col-4 text-center">
                        <a href="{{ route('settings') }}">Settings</a>
                    </div>
                </div>
            </li>
            <li class="user-footer">
                <div class="float-end">
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
    <li class="nav-item {{ Route::is('auth.register') ? 'active' : null }}"><a href="{{ route('auth.register') }}" class="nav-link">Register</a></li>
    <li class="nav-item {{ Route::is('auth.login') ? 'active' : null }}"><a href="{{ route('auth.login') }}" class="nav-link">Login</a></li>
@endauth
