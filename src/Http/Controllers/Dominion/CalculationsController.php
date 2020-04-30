<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Race;

class CalculationsController extends AbstractDominionController
{
    public function getDefense()
    {
        return view('pages.dominion.calculate-defense', [
            'races' => Race::orderBy('name')->get(),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }
}
