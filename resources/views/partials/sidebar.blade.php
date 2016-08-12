<div class="navbar-default sidebar" role="navigation">
    <div class="sidebar-nav navbar-collapse">
        <ul class="nav" id="side-menu">

            {{--<li class="sidebar-search">
                <div class="input-group custom-search-form">
                    <input type="text" class="form-control" placeholder="Search">
                    <span class="input-group-btn">
                            <button type="button" class="btn btn-default">
                                <i class="fa fa-search"></i>
                            </button>
                        </span>
                </div>
            </li>--}}

            <li class="{{ Request::is('/') ? 'active' : '' }}>">
                <a href="{{ route('home') }}"><i class="fa fa-home fa-fw"></i> Home</a>
            </li>

            @if (Auth::check())
                <li class="{{ Request::is('dashboard') ? 'active' : '' }}>">
                    <a href="{{ route('dashboard') }}"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                </li>
                {{--<li>
                    <a href="#"><i class="ra ra-sword ra-fw"></i> Test</a>
                </li>--}}
            @endif

        </ul>
    </div>
</div>
