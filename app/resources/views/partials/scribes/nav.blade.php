<div class="box box-primary">
    <div class="box-body no-padding">
        <div class="row">
            <div class="col-md-12 col-md-12">
                <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="{{ Route::is('scribes.races') ? 'active' : null }}"><a href="{{ route('scribes.races') }}">Races</a></li>
                        <li class="{{ Route::is('scribes.buildings') ? 'active' : null }}"><a href="{{ route('scribes.buildings') }}">Buildings</a></li>
                        <li class="{{ Route::is('scribes.operations') ? 'active' : null }}"><a href="{{ route('scribes.operations') }}">Operations</a></li>
                        <li class="{{ Route::is('scribes.spells') ? 'active' : null }}"><a href="{{ route('scribes.spells') }}">Spells</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>