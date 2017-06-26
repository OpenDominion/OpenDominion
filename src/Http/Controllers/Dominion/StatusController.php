<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Calculators\NetworthCalculator;
use OpenDominion\Services\Dominion\ProtectionService;

class StatusController extends AbstractDominionController
{
    public function getStatus()
    {
        return view('pages.dominion.status', [
            'dominionProtectionService' => app(ProtectionService::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class), // todo: remove or refactor $dominion->networth
            'populationCalculator' => app(PopulationCalculator::class),
        ]);
    }
}
