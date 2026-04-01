@php($user = Auth::user())

<ul class="nav nav-pills flex-column">

    <li class="nav-header">Staff</li>
    <li class="nav-item"><a href="{{ route('staff.index') }}" class="nav-link {{ Route::is('staff.index') ? 'active' : null }}">Dashboard</a></li>
    <li class="nav-item"><a href="{{ route('staff.audit') }}" class="nav-link {{ Route::is('staff.audit') ? 'active' : null }}">Audit Log</a></li>

    @if ($user->hasRole('Administrator'))
        <li class="nav-header">Anti-Cheat</li>
        <li class="nav-item"><a href="{{ route('staff.administrator.crosslogs') }}" class="nav-link {{ Route::is('staff.administrator.crosslogs') ? 'active' : null }}">Crosslogs</a></li>
        <li class="nav-item"><a href="{{ route('staff.administrator.invasions') }}" class="nav-link {{ Route::is('staff.administrator.invasions') ? 'active' : null }}">Invasions</a></li>
        <li class="nav-item"><a href="{{ route('staff.administrator.theft') }}" class="nav-link {{ Route::is('staff.administrator.theft') ? 'active' : null }}">Theft</a></li>

        <li class="nav-header">Administrator</li>
        <li class="nav-item"><a href="{{ route('staff.administrator.dominions.index') }}" class="nav-link {{ Route::is('staff.administrator.dominions.*') ? 'active' : null }}">Dominions</a></li>
        <li class="nav-item"><a href="{{ route('staff.administrator.raids.index') }}" class="nav-link {{ Route::is('staff.administrator.raids.*') ? 'active' : null }}">Raids</a></li>
        <li class="nav-item"><a href="{{ route('staff.administrator.users.index') }}" class="nav-link {{ Route::is('staff.administrator.users.*') ? 'active' : null }}">Users</a></li>
    @endif

    @if ($user->hasRole('Developer'))
        <li class="nav-header">Developer</li>
        <li class="nav-item"><a href="#" class="nav-link">Error Logs</a></li>
    @endif

    @if ($user->hasRole('Moderator'))
        <li class="nav-header">Moderator</li>
        <li class="nav-item"><a href="{{ route('staff.moderator.dominions.index') }}" class="nav-link {{ Route::is('staff.moderator.dominions.*') ? 'active' : null }}">Dominions</a></li>
        {{--<li class="nav-item"><a href="#" class="nav-link">Flagged Posts</a></li>--}}
    @endif

</ul>
