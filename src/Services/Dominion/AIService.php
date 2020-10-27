<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\QueueService;
use RuntimeException;

/**
 * Personalities
 * inactive|explorer|converter|attacker
 * 
 * Activity Level
 * every 12 hours + 0-80%
 * 
 * Explorer
 * bonuses
 *  - simulator needs to track tick count and reset bonuses
 * unlock techs
 *  - tech path
 * self spells
 *  - spells to use
 * invest
 *  - thresholds for investment
 * buildings
 *  - buildings to keep at %
 *  - employment
 *  - buildings to reach X
 * military
 *  - train up to X% of others/top OP
 * explore
 *  - based on build
 * release
 *  - release draftees
 */

class AIService
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var ConstructActionService */
    protected $constructActionService;

    /** @var ConstructionCalculator */
    protected $constructionCalculator;

    /** @var ExploreActionService */
    protected $exploreActionService;

    /** @var ExplorationCalculator */
    protected $explorationCalculator;

    /** @var ImproveActionService */
    protected $improveActionService;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ReleaseActionService */
    protected $releaseActionService;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var TrainActionService */
    protected $trainActionService;

    /** @var TrainingCalculator */
    protected $trainingCalculator;

    /**
     * AIService constructor.
     */
    public function __construct()
    {
        // Calculators
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->constructionCalculator = app(ConstructionCalculator::class);
        $this->explorationCalculator = app(ExplorationCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->trainingCalculator = app(TrainingCalculator::class);

        // Services
        $this->queueService = app(QueueService::class);

        // Action Services
        $this->constructActionService = app(ConstructActionService::class);
        $this->exploreActionService = app(ExploreActionService::class);
        $this->improveActionService = app(ImproveActionService::class);
        $this->releaseActionService = app(ReleaseActionService::class);
        $this->spellActionService = app(SpellActionService::class);
        $this->trainActionService = app(TrainActionService::class);
    }

    public function performActions(Dominion $dominion)
    {
        // Check activity level
        // TODO: return if !random_chance

        $totalLand = $this->landCalculator->getTotalLandIncoming($dominion);

        // Spells
        // TODO: check which spells to maintain in config
        if (!$this->spellCalculator->isSpellActive($dominion, 'midas_touch')) {
            $this->spellActionService->castSpell($dominion, 'midas_touch');
        }
        // Firewalker Only
        if (!$this->spellCalculator->isSpellActive($dominion, 'alchemist_flame')) {
            $this->spellActionService->castSpell($dominion, 'alchemist_flame');
        }

        // Construction
        // TODO: attacker rezones
        // TODO: get building types from config
        $maxAfford = $this->constructionCalculator->getMaxAfford($dominion);
        if ($maxAfford > 0) {
            $buildingsToConstruct = [];
            $barrenLand = $this->landCalculator->getBarrenLandByLandType($dominion);
            if ($barrenLand['forest'] > 0) {
                $buildingsToConstruct['building_lumberyard'] = min($maxAfford, $barrenLand['forest']);
                $maxAfford -= $buildingsToConstruct['building_lumberyard'];
            }
            if ($barrenLand['plain'] > 0) {
                $buildingsToConstruct['building_farm'] = min($maxAfford, $barrenLand['plain']);
                $maxAfford -= $buildingsToConstruct['building_farm'];
            }
            if ($barrenLand['swamp'] > 0) {
                $buildingsToConstruct['building_tower'] = min($maxAfford, $barrenLand['swamp']);
                $maxAfford -= $buildingsToConstruct['building_tower'];
            }
            if ($barrenLand['cavern'] > 0) {
                if (($dominion->building_diamond_mine + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_diamond_mine')) < 600) {
                    $buildingsToConstruct['building_diamond_mine'] = min($maxAfford, $barrenLand['cavern']);
                    $maxAfford -= $buildingsToConstruct['building_diamond_mine'];
                } else {
                    // Firewalker Only
                    $buildingsToConstruct['building_home'] = min($maxAfford, $barrenLand['cavern']);
                    $maxAfford -= $buildingsToConstruct['building_home'];
                }
            }
            $this->constructActionService->construct($dominion, $buildingsToConstruct);
        }

        // Military
        // TODO: get unit types from config
        // TODO: check neighboring dominions
        $defense = $this->militaryCalculator->getDefensivePower($dominion);
        $trainingQueue = $this->queueService->getTrainingQueueByPrefix($dominion, 'military_unit');
        $incomingTroops = $trainingQueue->mapWithKeys(function($queue) {
            return [str_replace('military_unit', '', $queue->resource) => $queue->amount];
        })->toArray();
        $incomingDefense = $this->militaryCalculator->getDefensivePower($dominion, null, null, $incomingTroops, 0, true, true);
        if (($defense + $incomingDefense) < ($totalLand * 5)) {
            $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)['unit3'];
            if ($maxAfford > 0) {
                $this->trainActionService->train($dominion, ['military_unit3' => $maxAfford]);
            }
        }

        // Explore
        // TODO: get land types from config
        // TODO: handle division by zero
        // TODO: calcuate actual percentages needed
        $maxAfford = $this->explorationCalculator->getMaxAfford($dominion);
        if ($maxAfford > 0) {
            $landToExplore = [];
            $farmPercentage = (
                $dominion->building_farm
                + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_farm')
                + $this->queueService->getExplorationQueueTotalByResource($dominion, 'land_plain')
            ) / $totalLand;
            if ($farmPercentage < 0.065) {
                $landToExplore['land_plain'] = min($maxAfford, ceil((0.065 - $farmPercentage) * $totalLand));
                $maxAfford -= $landToExplore['land_plain'];
            }
            $lumberyardPercentage = (
                $dominion->building_lumberyard
                + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_lumberyard')
                + $this->queueService->getExplorationQueueTotalByResource($dominion, 'land_forest')
            ) / $totalLand;
            if ($lumberyardPercentage < 0.06) {
                $landToExplore['land_forest'] = min($maxAfford, ceil((0.06 - $lumberyardPercentage) * $totalLand));
                $maxAfford -= $landToExplore['land_forest'];
            }
            $towerPercentage = (
                $dominion->building_tower
                + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_tower')
                + $this->queueService->getExplorationQueueTotalByResource($dominion, 'land_swamp')
            ) / $totalLand;
            if ($towerPercentage < 0.05) {
                $landToExplore['land_swamp'] = min($maxAfford, ceil((0.05 - $towerPercentage) * $totalLand));
                $maxAfford -= $landToExplore['land_swamp'];
            }
            $landToExplore['land_cavern'] = $maxAfford;
            $this->exploreActionService->explore($dominion, $landToExplore);
        }

        // Improvements
        if ($dominion->resource_gems > 0) {
            $sciencePercentage = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'science');
            $keepPercentage = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'keep');
            $wallsPercentage = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');
            if ($keepPercentage < 0.20) {
                $this->improveActionService->improve($dominion, 'gems', ['keep' => $dominion->resource_gems]);
            } elseif ($sciencePercentage < 0.10) {
                $this->improveActionService->improve($dominion, 'gems', ['science' => $dominion->resource_gems]);
            } elseif ($wallsPercentage < 0.10) {
                $this->improveActionService->improve($dominion, 'gems', ['walls' => $dominion->resource_gems]);
            } else {
                $this->improveActionService->improve($dominion, 'gems', ['keep' => $dominion->resource_gems]);
            }
        }

        // Release
        if ($dominion->military_draftees > 0) {
            $this->releaseActionService->release($dominion, ['draftees' => $dominion->military_draftees]);
        }
    }
}
