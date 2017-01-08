<aside class="main-sidebar">
    <section class="sidebar">

        <ul class="sidebar-menu">
            @if(0) {{--dominion selected--}}

            @else

            @endif

            <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="#"><i class="fa fw-dashboard"></i> Dashboard</a></li>
        </ul>

{{--        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="http://placehold.it/160x160" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
                <p>{{ Auth::user()->display_name }}</p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header">HEADER</li>
            <li class="active"><a href="#"><i class="fa fa-link"></i> <span>Link</span></a></li>
            <li><a href="#"><i class="fa fa-link"></i> <span>Another Link</span></a></li>
        </ul>--}}

    </section>
</aside>
