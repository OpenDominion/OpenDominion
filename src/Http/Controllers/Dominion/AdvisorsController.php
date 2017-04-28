<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Services\DominionQueueService;

class AdvisorsController extends AbstractDominionController
{
    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsProduction()
    {
        $populationCalculator = resolve(PopulationCalculator::class);
        $productionCalculator = resolve(ProductionCalculator::class);

        return view('pages.dominion.advisors.production', compact(
            'populationCalculator',
            'productionCalculator'
        ));
    }

    public function getAdvisorsMilitary()
    {
        return view('pages.dominion.advisors.military');
    }

    public function getAdvisorsLand()
    {
        $landHelper = resolve(LandHelper::class);
        $landCalculator = resolve(LandCalculator::class);
        $dominionQueueService = resolve(DominionQueueService::class);

        return view('pages.dominion.advisors.land', compact(
            'landHelper',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function getAdvisorsConstruction()
    {
        $buildingHelper = resolve(BuildingHelper::class);
        $buildingCalculator = resolve(BuildingCalculator::class);
        $landCalculator = resolve(LandCalculator::class);
        $dominionQueueService = resolve(DominionQueueService::class);

        return view('pages.dominion.advisors.construction', compact(
            'buildingHelper',
            'buildingCalculator',
            'landCalculator',
            'dominionQueueService'
        ));
    }
}
