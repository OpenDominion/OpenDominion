@php($user = Auth::user())

<ul class="nav nav-stacked">

    <li class="header">Staff</li>
    <li class="{{ Route::is('staff.index') ? 'active' : null }}"><a href="{{ route('staff.index') }}">Dashboard</a></li>

    @if ($user->hasRole('Developer'))
        <li class="header">Developer</li>
        {{--<li><a href="#">Simulate</a></li>--}}
    @endif

    @if ($user->hasRole('Administrator'))
        <li class="header">Administrator</li>
        {{--<li><a href="#">Council</a></li>--}}
        <li class="{{ Route::is('staff.administrator.dominions.*') ? 'active' : null }}"><a href="{{ route('staff.administrator.dominions.index') }}">Dominions</a></li>
        {{--<li><a href="#">Realms</a></li>--}}
        {{--<li><a href="#">Rounds</a></li>--}}
        <li class="{{ Route::is('staff.administrator.users.*') ? 'active' : null }}"><a href="{{ route('staff.administrator.users.index') }}">Users</a></li>
    @endif

    @if ($user->hasRole('Moderator'))
        <li class="header">Moderator</li>
        {{--<li><a href="#">Flagged Posts</a></li>--}}
    @endif

</ul>
