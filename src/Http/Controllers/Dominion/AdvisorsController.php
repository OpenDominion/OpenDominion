<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\ExplorationQueueService;
use OpenDominion\Services\Dominion\Queue\LandIncomingQueueService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Services\Dominion\Queue\UnitsReturningQueueService;

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
        return view('pages.dominion.advisors.military', [
            'unitsReturningQueueService' => app(UnitsReturningQueueService::class),
            'trainingQueueService' => app(TrainingQueueService::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getAdvisorsLand()
    {
        return view('pages.dominion.advisors.land', [
            'explorationQueueService' => app(ExplorationQueueService::class),
            'landIncomingQueueService' => app(LandIncomingQueueService::class),
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
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

    public function getAdvisorsMagic()
    {
        return view('pages.dominion.advisors.magic', [
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
        ]);
    }

    public function getAdvisorsRankings()
    {
        // todo
    }

    public function getAdvisorsStatistics()
    {
        return view('pages.dominion.advisors.statistics', [
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
        ]);
    }
}
