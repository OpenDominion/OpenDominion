@auth
    @if (Auth::user()->isStaff())
        <li class="hidden-xs {{ Route::is('staff.*') ? 'active' : null }}">
            <a href="{{ route('staff.index') }}">Staff</a>
        </li>
    @endif
@endauth
