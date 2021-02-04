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

    public function getRequiredDefense(Dominion $dominion)
    {
        $defenseByDay = [
            '4'  => 5.5,
            '5'  => 8.5,
            '6'  => 10.5,
            '7'  => 12.0,
            '8'  => 15.0,
            '9'  => 17.0,
            '10' => 19.0,
            '11' => 21.5,
            '12' => 24.0,
            '13' => 26.5,
            '14' => 29.0,
            '15' => 31.5,
            '16' => 33.0,
            '17' => 35.0,
            '18' => 36.0,
            '19' => 37.5,
            '20' => 38.5,
            '21' => 40.5,
            '22' => 42.5,
            '23' => 44.0,
            '24' => 46.0,
            '25' => 48.0,
            '26' => 49.5,
            '27' => 51.0,
            '28' => 52.5,
            '29' => 54.0,
            '30' => 55.0,
            '31' => 57.0,
            '32' => 59.0,
            '33' => 60.5,
            '34' => 62.0,
            '35' => 63.5,
            '36' => 65.0,
            '37' => 66.5,
            '38' => 67.0,
            '39' => 68.5,
            '40' => 70.0,
            '41' => 71.0,
            '42' => 72.0,
            '43' => 73.0,
            '44' => 74.0,
            '45' => 75.0,
            '46' => 76.0,
            '47' => 77.0,
            '48' => 78.0,
            '49' => 79.0,
            '50' => 80.0
        ];

        if ($dominion->round->daysInRound() >= 4 && $dominion->round->daysInRound() <= 50) {
            // Defense starts 10% below chart, each invasion increases target DPA by 1%
            $invasionMultiplier = (1 + ($dominion->stat_defending_failure - 10) / 100);
            return $defenseByDay[$dominion->round->daysInRound()] * $invasionMultiplier;
        }

        return 3;
    }

    public function performActions(Dominion $dominion)
    {
        // TODO: Move to configuration file
        if ($dominion->race->name == 'Firewalker') {
            $config = [
                'active_chance' => '0.40', // 40% chance to log in
                'spells' => ['alchemist_flame', 'ares_call', 'midas_touch'],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.07 // maintain 7% farms
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 600 // build up to 600, then stop
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'home',
                        'amount' => -1 // no limit, when jobs available
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'alchemy',
                        'amount' => -1 // no limit, when jobs needed
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
                        'amount' => -1
                    ],
                    [
                        'unit' => 'spies',
                        'amount' => 0.05 // maintain 0.05 SPA
                    ],
                    [
                        'unit' => 'wizards',
                        'amount' => 0.05 // maintain 0.05 WPA
                    ]
                ]
            ];
        } else {
            return;
        }

        // Check activity level
        if (random_chance($config['active_chance'])) {
            return;
        }

        $totalLand = $this->landCalculator->getTotalLandIncoming($dominion);

        // Spells
        foreach ($config['spells'] as $spell) {
            if (!$this->spellCalculator->isSpellActive($dominion, $spell)) {
                $this->spellActionService->castSpell($dominion, $spell);
            }
        }

        // Construction
        // TODO: calcuate actual percentages needed for farms, towers, etc
        $buildingsToConstruct = [];
        $maxAfford = $this->constructionCalculator->getMaxAfford($dominion);
        $barrenLand = $this->landCalculator->getBarrenLandByLandType($dominion);
        foreach ($config['build'] as $command) {
            if ($maxAfford > 0) {
                $buildingCount = (
                    $dominion->{'building_'.$command['building']}
                    + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_'.$command['building'])
                );
                $buildingPercentage = $buildingCount / $totalLand;

                if ($barrenLand[$command['land_type']] > 0) {
                    if ($command['amount'] == -1) {
                        // Unlimited
                        // TODO: check jobs
                        $buildingsToConstruct['building_'.$command['building']] = min($maxAfford, $barrenLand[$command['land_type']]);
                        $maxAfford -= $buildingsToConstruct['building_'.$command['building']];
                    } elseif ($command['amount'] < 1 && $buildingPercentage < $command['amount']) {
                        // Percentage based
                        $buildingsToConstruct['building_'.$command['building']] = min($maxAfford, $barrenLand[$command['land_type']], ceil(($command['amount'] - $buildingPercentage) * $totalLand));
                        $maxAfford -= $buildingsToConstruct['building_'.$command['building']];
                    } else {
                        // Limited
                        if ($buildingCount < $command['amount']) {
                            $buildingsToConstruct['building_'.$command['building']] = min($maxAfford, $barrenLand[$command['land_type']], $command['amount'] - $buildingCount);
                            $maxAfford -= $buildingsToConstruct['building_'.$command['building']];
                        }
                    }
                }
            }
        }

        if (!empty($buildingsToConstruct)) {
            $this->constructActionService->construct($dominion, $buildingsToConstruct);
        }

        // Military
        // TODO: check neighboring dominions?
        $defense = $this->militaryCalculator->getDefensivePower($dominion);
        $trainingQueue = $this->queueService->getTrainingQueueByPrefix($dominion, 'military_unit');
        $incomingTroops = $trainingQueue->mapWithKeys(function($queue) {
            return [str_replace('military_unit', '', $queue->resource) => $queue->amount];
        })->toArray();
        $incomingDefense = $this->militaryCalculator->getDefensivePower($dominion, null, null, $incomingTroops, 0, true, true);
        foreach ($config['military'] as $command) {
            if ($command == 'spies') {
                // Train spies
                $spyRatio = $this->militaryCalculator->getSpyRatio($dominion, 'defense');
                if ($spyRatio < $command['amount']) {
                    $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']];
                }
            } elseif ($command == 'wizards') {
                // Train wizards
                $wizardRatio = $this->militaryCalculator->getWizardRatio($dominion, 'defense');
                if ($wizardRatio < $command['amount']) {
                    $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']];
                }
            } else {
                // Train military
                $defenseRequired = $totalLand * $this->getRequiredDefense($dominion);
                if (($defense + $incomingDefense) < $defenseRequired) {
                    $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']];
                }
            }
            if ($maxAfford > 0) {
                $this->trainActionService->train($dominion, ['military_'.$command['unit'] => $maxAfford]);
            }
        }

        // Explore
        // TODO: calcuate actual percentages needed for farms, towers, etc
        $landToExplore = [];
        $maxAfford = $this->explorationCalculator->getMaxAfford($dominion);
        foreach ($config['build'] as $command) {
            if ($maxAfford > 0) {
                $buildingCount = (
                    $dominion->{'building_'.$command['building']}
                    + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_'.$command['building'])
                    + $this->queueService->getExplorationQueueTotalByResource($dominion, 'land_'.$command['land_type'])
                );
                $buildingPercentage = $buildingCount / $totalLand;

                if ($command['amount'] == -1) {
                    // Unlimited
                    // TODO: check jobs
                    $landToExplore['land_'.$command['land_type']] = $maxAfford;
                    $maxAfford -= $landToExplore['land_'.$command['land_type']];
                } elseif ($command['amount'] < 1 && $buildingPercentage < $command['amount']) {
                    // Percentage based
                    $landToExplore['land_'.$command['land_type']] = min($maxAfford, ceil(($command['amount'] - $buildingPercentage) * $totalLand));
                    $maxAfford -= $landToExplore['land_'.$command['land_type']];
                } else {
                    // Limited
                    if ($buildingCount < $command['amount']) {
                        $buildingsToConstruct['building_'.$command['building']] = min($maxAfford, $command['amount'] - $buildingCount);
                        $maxAfford -= $buildingsToConstruct['building_'.$command['building']];
                    }
                }
            }
        }

        if (!empty($landToExplore)) {
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

        /*
        $ai = app(\OpenDominion\Services\Dominion\AIService::class);
        $ts = app(\OpenDominion\Services\Dominion\TickService::class);
        foreach(range(1,24) as $range) { $ai->performActions($dominion); $ts->performTick($dominion->round, $dominion); }
        */
    }
}