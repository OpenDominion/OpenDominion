<li class="nav-item dropdown">
    <a href="#" class="nav-link" data-bs-toggle="dropdown" aria-label="Links">
        <i class="fa fa-ellipsis-vertical fa-fw"></i>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a href="{{ route('scribes.overview') }}" class="dropdown-item d-flex align-items-center gap-2">
                <i class="fa fa-book fa-fw"></i> Scribes
            </a>
        </li>
        <li>
            <a href="{{ route('valhalla.index') }}" class="dropdown-item d-flex align-items-center gap-2">
                <i class="fa fa-trophy fa-fw"></i> Valhalla
            </a>
        </li>
        <li>
            <a href="https://wiki.opendominion.net" target="_blank" class="dropdown-item d-flex align-items-center gap-2">
                <i class="fa fa-external-link fa-fw"></i> Wiki
            </a>
        </li>
        @auth
            @if (Auth::user()->isStaff())
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a href="{{ route('staff.index') }}" class="dropdown-item d-flex align-items-center gap-2">
                        <i class="fa fa-star fa-fw"></i> Staff
                    </a>
                </li>
            @else
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a href="{{ route('user-agreement') }}" class="dropdown-item d-flex align-items-center gap-2">
                        <i class="fa fa-gavel fa-fw"></i> Rules
                    </a>
                </li>
            @endif
        @endauth
    </ul>
</li>
