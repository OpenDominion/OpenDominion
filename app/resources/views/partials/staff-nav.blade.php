@auth
    @if (Auth::user()->isStaff())
        <li class="nav-item {{ $navClass ?? '' }} {{ Route::is('staff.*') ? 'active' : null }}">
            <a href="{{ route('staff.index') }}" class="nav-link">Staff</a>
        </li>
    @else
        <li class="nav-item {{ $navClass ?? '' }} {{ Route::is('user-agreement') ? 'active' : null }}">
            <a href="{{ route('user-agreement') }}" class="nav-link">Rules</a>
        </li>
    @endif
@endauth
