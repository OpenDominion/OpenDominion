<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a href="{{ route('scribes.overview') }}" class="nav-link {{ Route::is('scribes.overview') ? 'active' : null }}">Overview</a></li>
    <li class="nav-item"><a href="{{ route('scribes.races') }}" class="nav-link {{ Route::is('scribes.races', 'scribes.race', 'scribes.all-races') ? 'active' : null }}">Races</a></li>
    <li class="nav-item"><a href="{{ route('scribes.construction') }}" class="nav-link {{ Route::is('scribes.construction') ? 'active' : null }}">Construction</a></li>
    <li class="nav-item"><a href="{{ route('scribes.espionage') }}" class="nav-link {{ Route::is('scribes.espionage') ? 'active' : null }}">Espionage</a></li>
    <li class="nav-item"><a href="{{ route('scribes.magic') }}" class="nav-link {{ Route::is('scribes.magic') ? 'active' : null }}">Magic</a></li>
    <li class="nav-item"><a href="{{ route('scribes.techs') }}" class="nav-link {{ Route::is('scribes.techs', 'scribes.legacy-techs') ? 'active' : null }}">Techs</a></li>
    <li class="nav-item"><a href="{{ route('scribes.heroes') }}" class="nav-link {{ Route::is('scribes.heroes') ? 'active' : null }}">Heroes</a></li>
    <li class="nav-item"><a href="{{ route('scribes.wonders') }}" class="nav-link {{ Route::is('scribes.wonders') ? 'active' : null }}">Wonders</a></li>
</ul>
