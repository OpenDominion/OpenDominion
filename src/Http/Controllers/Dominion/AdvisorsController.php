<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\ExplorationQueueService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;

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
            'trainingQueueService' => app(TrainingQueueService::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getAdvisorsLand()
    {
        return view('pages.dominion.advisors.land', [
            'explorationQueueService' => app(ExplorationQueueService::class),
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
            //
        ]);
    }

    public function getAdvisorsRankings(string $type = null)
    {
        if (($type === null) || !in_array($type, ['land', 'networth'], true)) {
            return redirect()->route('dominion.advisors.rankings', 'land');
        }

        $selectedDominion = $this->getSelectedDominion();

        $rankings = \DB::table('daily_rankings')
            ->where('round_id', $selectedDominion->round_id)
            ->orderBy($type . '_rank')
            ->paginate(10);

        return view('pages.dominion.advisors.rankings', [
            'type' => $type,
            'rankings' => $rankings,
        ]);
    }

    public function getAdvisorsStatistics()
    {
        return view('pages.dominion.advisors.statistics', [
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
        ]);
    }
}
