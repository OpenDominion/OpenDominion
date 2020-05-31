<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\RankingsService;

class AdvisorsController extends AbstractDominionController
{
    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsProduction(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.production', [
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsMilitary(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.military', [
            'queueService' => app(QueueService::class),
            'unitHelper' => app(UnitHelper::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsLand(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.land', [
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'queueService' => app(QueueService::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsConstruction(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.construction', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'landCalculator' => app(LandCalculator::class),
            'queueService' => app(QueueService::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsMagic(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.magic', [
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsRankings(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.rankings', [
            'rankingsHelper' => app(RankingsHelper::class),
            'rankingsService' => app(RankingsService::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsStatistics(Dominion $target = null)
    {
        $this->guardPackRealm($target);
        return view('pages.dominion.advisors.statistics', [
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'targetDominion' => $target
        ]);
    }

    private function guardPackRealm(?Dominion $target)
    {
        if($target == null) {
            return;
        }

        if ($target->user->getSetting('packadvisors') === false) {
            throw new GameException('This user has opted not to share their advisors.');
        }

        $dominion = $this->getSelectedDominion();

        if ($dominion->pack_id == null || $dominion->pack_id !== $target->pack_id) {
            throw new GameException('You are only allowed to look at dominions in your pack.');
        }

        if ($dominion->locked_at !== null) {
            throw new GameException('Locked dominions are not allowed to look at realm advisors.');
        }
    }
}
