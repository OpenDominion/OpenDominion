<div class="card card-primary">
    <div class="card-body no-padding">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="navbar-collapse">
                    <ul class="nav navbar-nav scribes-menu">
                        <li class="nav-item {{ Route::is('scribes.overview') ? 'active' : null }}"><a href="{{ route('scribes.overview') }}" class="nav-link">Overview</a></li>
                        <li class="nav-item {{ Route::is('scribes.races') ? 'active' : null }}"><a href="{{ route('scribes.races') }}" class="nav-link">Races</a></li>
                        <li class="nav-item {{ Route::is('scribes.construction') ? 'active' : null }}"><a href="{{ route('scribes.construction') }}" class="nav-link">Construction</a></li>
                        <li class="nav-item {{ Route::is('scribes.espionage') ? 'active' : null }}"><a href="{{ route('scribes.espionage') }}" class="nav-link">Espionage</a></li>
                        <li class="nav-item {{ Route::is('scribes.magic') ? 'active' : null }}"><a href="{{ route('scribes.magic') }}" class="nav-link">Magic</a></li>
                        <li class="nav-item {{ Route::is('scribes.techs') ? 'active' : null }}"><a href="{{ route('scribes.techs') }}" class="nav-link">Techs</a></li>
                        <li class="nav-item {{ Route::is('scribes.heroes') ? 'active' : null }}"><a href="{{ route('scribes.heroes') }}" class="nav-link">Heroes</a></li>
                        <li class="nav-item {{ Route::is('scribes.wonders') ? 'active' : null }}"><a href="{{ route('scribes.wonders') }}" class="nav-link">Wonders</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
