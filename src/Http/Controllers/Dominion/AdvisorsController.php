<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Contracts\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Contracts\Services\Dominion\Queue\ExplorationQueueService;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;

class AdvisorsController extends AbstractDominionController
{
    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsProduction()
    {
        return view('pages.dominion.advisors.production', [
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
        ]);
    }

    public function getAdvisorsMilitary()
    {
        return view('pages.dominion.advisors.military');
    }

    public function getAdvisorsLand()
    {
        return view('pages.dominion.advisors.land', [
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'explorationQueueService' => app(ExplorationQueueService::class),
        ]);
    }

    public function getAdvisorsConstruction()
    {
        return view('pages.dominion.advisors.construction', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'constructionQueueService' => app(ConstructionQueueService::class),
            'landCalculator' => app(LandCalculator::class),
        ]);
    }
}
