<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;

class StatusController extends AbstractDominionController
{
    public function getStatus()
    {
        $landCalculator = resolve(LandCalculator::class);
        $populationCalculator = resolve(PopulationCalculator::class);

        // todo: make status view a partial for here + other dominion status and include stuff like OOP here?

        return view('pages.dominion.status', compact(
            'landCalculator',
            'populationCalculator'
        ));
    }
}
