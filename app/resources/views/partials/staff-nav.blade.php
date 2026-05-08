@auth
    @if (Auth::user()->isStaff())
        <li class="nav-item {{ $navClass ?? '' }} {{ Route::is('staff.*') ? 'active' : null }}">
            <a href="{{ route('staff.index') }}" class="nav-link">Staff</a>
        </li>
    @endif
@endauth
