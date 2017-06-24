<aside class="main-sidebar">
    <section class="sidebar">

        @if (isset($selectedDominion))
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="{{ Gravatar::src(Auth::user()->email, 160) }}" class="img-circle" alt="{{ Auth::user()->display_name }}">
                </div>
                <div class="pull-left info">
                    <p>{{ $selectedDominion->name }}</p>
                    <a href="{{ route('dominion.realm') }}">{{ $selectedDominion->realm->name }} (#{{ $selectedDominion->realm->number }})</a>
                </div>
            </div>
        @endif

        <ul class="sidebar-menu">
            @if (isset($selectedDominion))

                <li class="{{ Route::is('dominion.status') ? 'active' : null }}"><a href="{{ route('dominion.status') }}"><i class="ra ra-capitol ra-fw"></i> <span>Status</span></a></li>
                <li class="{{ Route::is('dominion.advisors.*') ? 'active' : null }}"><a href="{{ route('dominion.advisors') }}"><i class="fa fa-group fa-fw"></i> <span>Advisors</span></a></li>

                <li class="header">DOMINION</li>
                <li class="{{ Route::is('dominion.explore') ? 'active' : null }}"><a href="{{ route('dominion.explore') }}"><i class="fa fa-search fa-fw"></i> <span>Explore</span></a></li>
                <li class="{{ Route::is('dominion.construction') ? 'active' : null }}"><a href="{{ route('dominion.construction') }}"><i class="fa fa-home fa-fw"></i> <span>Construction</span></a></li>
                <li class="{{ Route::is('dominion.rezone') ? 'active' : null }}"><a href="{{ route('dominion.rezone') }}"><i class="ra ra-cycle ra-fw"></i> <span>Re-zone Land</span></a></li>
                {{--<li class="{{ Route::is('dominion.improvements') ? 'active' : null }}"><a href="{{ route('dominion.improvements') }}"><i class="fa fa-arrow-up fa-fw"></i> <span>Improvements</span></a></li>--}}
                {{--<li class="{{ Route::is('dominion.national-bank') ? 'active' : null }}"><a href="{{ route('dominion.national-bank') }}"><i class="fa fa-money fa-fw"></i> <span>National Bank</span></a></li>--}}

                <li class="header">BLACK OPS</li>
                <li class="{{ Route::is('dominion.military') ? 'active' : null }}"><a href="{{ route('dominion.military') }}"><i class="ra ra-sword ra-fw"></i> <span>Military</span></a></li>

                {{--<li class="header">COMMS?</li>--}}

                <li class="header">REALM</li>
                <li class="{{ Route::is('dominion.realm') ? 'active' : null }}"><a href="{{ route('dominion.realm') }}"><i class="ra ra-circle-of-circles ra-fw"></i> <span>The Realm</span></a></li>

                {{--<li class="header">MISC</li>--}}

                @if (app()->environment() !== 'production')
                    <li class="header">SECRET</li>
                    <li class="{{ Request::is('dominion/debug') ? 'active' : null }}"><a href="{{ url('dominion/debug') }}"><i class="ra ra-dragon ra-fw"></i> <span>Debug Page</span></a></li>
                @endif

            @else

                <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="ra ra-capitol ra-fw"></i> <span>Select your Dominion</span></a></li>

            @endif

{{--            <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="fa fa-dashboard fa-fw"></i> <span>Dashboard</span></a></li>--}}
        </ul>

    </section>
</aside>
