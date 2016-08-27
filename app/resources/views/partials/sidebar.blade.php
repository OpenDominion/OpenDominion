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

            <li>
                <a href="{{ route('home') }}"><i class="fa fa-home fa-fw"></i> Home</a>
            </li>

            @if (Auth::check())
                <li>
                    <a href="{{ route('dashboard') }}"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                </li>

                @if ($dominion = Request::route()->getParameter('dominion'))
                    <li>
                        <a href="{{ route('dominion.status', $dominion) }}"><i class="ra ra-capitol ra-fw"></i> Status</a>
                    </li>
                @endif
            @endif

        </ul>
    </div>
</div>
