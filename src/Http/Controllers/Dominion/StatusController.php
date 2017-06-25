<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Services\DominionProtectionService;

class StatusController extends AbstractDominionController
{
    public function getStatus()
    {
        $dominionProtectionService = app(DominionProtectionService::class);
        $dominionProtectionService->setDominion($this->getSelectedDominion());
        $landCalculator = app(LandCalculator::class);
        $populationCalculator = app(PopulationCalculator::class);

        return view('pages.dominion.status', compact(
            'dominionProtectionService',
            'landCalculator',
            'populationCalculator'
        ));
    }
}
