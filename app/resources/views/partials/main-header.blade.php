<nav class="app-header navbar navbar-expand bg-body" data-bs-theme="dark">
    <div class="container-fluid">

        <!-- Sidebar toggle -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fa fa-bars"></i>
                </a>
            </li>
        </ul>

        <!-- Brand -->
        <a href="{{ url('') }}" class="navbar-brand ms-2">
            Open<b>Dominion</b>
        </a>

        <!-- Navbar Right Menu -->
        <ul class="navbar-nav ms-auto">
            <!-- Color Mode -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link" data-bs-toggle="dropdown" aria-label="Color mode">
                    <i class="fa fa-fw" id="color-mode-icon"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2" data-color-mode-value="light">
                            <i class="fa fa-sun fa-fw"></i> Light
                            <i class="fa fa-check ms-auto color-mode-check"></i>
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2" data-color-mode-value="dark">
                            <i class="fa fa-moon fa-fw"></i> Dark
                            <i class="fa fa-check ms-auto color-mode-check"></i>
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2" data-color-mode-value="auto">
                            <i class="fa fa-circle-half-stroke fa-fw"></i> Auto
                            <i class="fa fa-check ms-auto color-mode-check"></i>
                        </button>
                    </li>
                </ul>
            </li>

            @include('partials.scribes-nav')
            @include('partials.valhalla-nav')
            @include('partials.staff-nav')
            @include('partials.wiki-nav')
            @include('partials.notification-nav')
            @include('partials.auth-user-nav')
        </ul>

    </div>
</nav>
