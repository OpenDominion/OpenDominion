<aside class="main-sidebar">
    <section class="sidebar">

        @if (isset($selectedDominion))
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="http://placehold.it/160x160" class="img-circle" alt="User Image">
                </div>
                <div class="pull-left info">
                    <p>{{ $selectedDominion->name }}</p>
                    <a href="#">{{ $selectedDominion->realm->name }} (#{{ $selectedDominion->realm->number }})</a>
                </div>
            </div>
        @endif

        <ul class="sidebar-menu">
            @if (isset($selectedDominion))
                <li class="header">DOMINION</li>
                <li class="{{ Route::is('dominion.status') ? 'active' : null }}"><a href="{{ route('dominion.status') }}"><i class="ra ra-capitol ra-fw"></i> <span>Status</span></a></li>

                <li class="header">MISC</li>
            @else
            @endif

            <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="fa fa-dashboard fa-fw"></i> <span>Dashboard</span></a></li>
        </ul>

    </section>
</aside>
