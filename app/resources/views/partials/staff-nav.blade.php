@auth
    @if (Auth::user()->isStaff())
        <li class="{{ Route::is('staff.*') ? 'active' : null }}">
            <a href="{{ route('staff.index') }}">Staff</a>
        </li>
    @else
        <li class="{{ Route::is('user-agreement') ? 'active' : null }}">
            <a href="{{ route('user-agreement') }}">Rules</a>
        </li>
    @endif
@endauth
