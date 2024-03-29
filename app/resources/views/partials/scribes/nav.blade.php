<div class="box box-primary">
    <div class="box-body no-padding">
        <div class="row">
            <div class="col-md-12 col-md-12">
                <div class="navbar-collapse">
                    <ul class="nav navbar-nav scribes-menu">
                        <li class="{{ Route::is('scribes.overview') ? 'active' : null }}"><a href="{{ route('scribes.overview') }}">Overview</a></li>
                        <li class="{{ Route::is('scribes.races') ? 'active' : null }}"><a href="{{ route('scribes.races') }}">Races</a></li>
                        <li class="{{ Route::is('scribes.construction') ? 'active' : null }}"><a href="{{ route('scribes.construction') }}">Construction</a></li>
                        <li class="{{ Route::is('scribes.espionage') ? 'active' : null }}"><a href="{{ route('scribes.espionage') }}">Espionage</a></li>
                        <li class="{{ Route::is('scribes.magic') ? 'active' : null }}"><a href="{{ route('scribes.magic') }}">Magic</a></li>
                        <li class="{{ Route::is('scribes.techs') ? 'active' : null }}"><a href="{{ route('scribes.techs') }}">Techs</a></li>
                        <li class="{{ Route::is('scribes.heroes') ? 'active' : null }}"><a href="{{ route('scribes.heroes') }}">Heroes</a></li>
                        <li class="{{ Route::is('scribes.wonders') ? 'active' : null }}"><a href="{{ route('scribes.wonders') }}">Wonders</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>