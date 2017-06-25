<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Services\Dominion\QueueService;

class AdvisorsController extends AbstractDominionController
{
    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsProduction()
    {
        $populationCalculator = app(PopulationCalculator::class);
        $productionCalculator = app(ProductionCalculator::class);

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
        $landHelper = app(LandHelper::class);
        $landCalculator = app(LandCalculator::class);
        $dominionQueueService = app(QueueService::class);

        return view('pages.dominion.advisors.land', compact(
            'landHelper',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function getAdvisorsConstruction()
    {
        $buildingHelper = app(BuildingHelper::class);
        $buildingCalculator = app(BuildingCalculator::class);
        $landCalculator = app(LandCalculator::class);
        $dominionQueueService = app(QueueService::class);

        return view('pages.dominion.advisors.construction', compact(
            'buildingHelper',
            'buildingCalculator',
            'landCalculator',
            'dominionQueueService'
        ));
    }
}
