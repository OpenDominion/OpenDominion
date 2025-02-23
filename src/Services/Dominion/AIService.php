<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Log;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\QueueService;
use RuntimeException;

class AIService
{
    /** @var Carbon */
    protected $now;

    /** @var AIHelper */
    protected $aiHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var ChangeDraftRateActionService */
    protected $changeDraftRateActionService;

    /** @var ConstructActionService */
    protected $constructActionService;

    /** @var ConstructionCalculator */
    protected $constructionCalculator;

    /** @var DailyBonusesActionService */
    protected $dailyBonusesActionService;

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

    /** @var LandHelper */
    protected $landHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var ReleaseActionService */
    protected $releaseActionService;

    /** @var RezoneActionService */
    protected $rezoneActionService;

    /** @var RezoningCalculator */
    protected $rezoningCalculator;

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
        $this->now = now();

        // Calculators
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->constructionCalculator = app(ConstructionCalculator::class);
        $this->explorationCalculator = app(ExplorationCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->rezoningCalculator = app(RezoningCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->trainingCalculator = app(TrainingCalculator::class);

        // Helpers
        $this->aiHelper = app(AIHelper::class);

        // Services
        $this->queueService = app(QueueService::class);

        // Action Services
        $this->changeDraftRateActionService = app(ChangeDraftRateActionService::class);
        $this->constructActionService = app(ConstructActionService::class);
        $this->dailyBonusesActionService = app(DailyBonusesActionService::class);
        $this->exploreActionService = app(ExploreActionService::class);
        $this->improveActionService = app(ImproveActionService::class);
        $this->releaseActionService = app(ReleaseActionService::class);
        $this->rezoneActionService = app(RezoneActionService::class);
        $this->spellActionService = app(SpellActionService::class);
        $this->trainActionService = app(TrainActionService::class);
    }

    public function executeAI()
    {
        Log::debug('AI started');

        $activeRounds = Round::active()->get();

        foreach ($activeRounds as $round) {
            $dominions = $round->activeDominions()
                ->where('ai_enabled', true)
                ->with([
                    'queues',
                    'race',
                    'round',
                ])
                ->get();

            foreach ($dominions as $dominion) {
                try {
                    $this->performActions($dominion);
                    usleep(200 * 1000);
                } catch (Exception $e) {
                    continue;
                }
            }

            Log::info(sprintf(
                'Executed actions for %s AI dominions in %s ms in %s',
                number_format($dominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));
        }

        Log::debug('AI finished');
    }

    public function performActions(Dominion $dominion)
    {
        $actionsTaken = 0;
        $config = $dominion->ai_config;

        // Automated player actions
        if ($dominion->user_id !== null) {
            $currentTick = $dominion->round->getTick();
            if (isset($config[$currentTick])) {
                foreach ($config[$currentTick] as $instruction) {
                    if ($dominion->daily_actions == 0 && $instruction['action'] !== 'daily_bonus') {
                        continue;
                    }
                    $dominion->refresh();
                    try {
                        switch ($instruction['action']) {
                            case 'construct':
                                $landType = $this->landHelper->getLandTypeForBuildingByRace($instruction['key'], $dominion->race);
                                $barrenLand = $this->landCalculator->getBarrenLandByLandType($dominion)[$landType];
                                $maxAfford = min(
                                    $barrenLand,
                                    $instruction['amount'],
                                    $this->constructionCalculator->getMaxAfford($dominion)
                                );
                                if ($maxAfford > 0) {
                                    $this->constructActionService->construct($dominion, ['building_' . $instruction['key'] => $maxAfford]);
                                }
                                break;
                            case 'daily_bonus':
                                if ($instruction['key'] === 'land') {
                                    $this->dailyBonusesActionService->claimLand($dominion);
                                }
                                if ($instruction['key'] === 'platinum') {
                                    $this->dailyBonusesActionService->claimPlatinum($dominion);
                                }
                                $actionsTaken--;
                                break;
                            case 'draft_rate':
                                $this->changeDraftRateActionService->changeDraftRate($dominion, $instruction['amount']);
                                break;
                            case 'explore':
                                $maxAfford = min(
                                    $instruction['amount'],
                                    $this->explorationCalculator->getMaxAfford($dominion)
                                );
                                if ($maxAfford > 0) {
                                    $this->exploreActionService->explore($dominion, ['land_' . $instruction['key'] => $maxAfford]);
                                }
                                break;
                            case 'release':
                                $maxAfford = min(
                                    $instruction['amount'],
                                    $dominion->military_draftees
                                );
                                if ($maxAfford > 0) {
                                    $this->releaseActionService->release($dominion, ['draftees' => $maxAfford]);
                                }
                                break;
                            case 'rezone':
                                $maxAfford = min(
                                    $instruction['amount'],
                                    $this->rezoningCalculator->getMaxAfford($dominion)
                                );
                                if ($maxAfford > 0) {
                                    $this->rezoneActionService->rezone($dominion, [$instruction['key'] => $maxAfford], [$instruction['key2'] => $maxAfford]);
                                }
                                break;
                            case 'spell':
                                $this->spellActionService->castSpell($dominion, $instruction['key']);
                                break;
                            case 'train':
                                $maxAfford = min(
                                    $instruction['amount'],
                                    $this->trainingCalculator->getMaxTrainable($dominion)[$instruction['key']]
                                );
                                if ($maxAfford > 0) {
                                    $this->trainActionService->train($dominion, ['military_' . $instruction['key'] => $maxAfford]);
                                }
                                break;
                        }
                        $actionsTaken++;
                    } catch (GameException $e) {
                        Log::error($e);
                    }
                }
                unset($config[$currentTick]);
            }

            // Remove any failed/missed actions
            foreach ($config as $tick => $actions) {
                if ($tick < $currentTick) {
                    unset($config[$tick]);
                }
            }

            $dominion->ai_enabled = !empty($config);
            $dominion->ai_config = $config;
            if ($actionsTaken > 0) {
                $dominion->daily_actions -= 1;
            }
            $dominion->save();

            return;
        }

        // Set max draft rate for active NPDs
        if ($dominion->draft_rate < 90) {
            $dominion->draft_rate = 90;
            $dominion->save();
        }

        // Check activity level
        if (random_chance($config['active_chance'])) {
            return;
        }

        $totalLand = $this->landCalculator->getTotalLandIncoming($dominion);
        $incomingLand = $this->queueService->getExplorationQueueTotal($dominion);

        // Spells
        try {
            $this->castSpells($dominion, $config);
        } catch (GameException $e) {
            // Get out, you old Wight! Vanish in the sunlight!
        }

        // Construction
        try {
            $this->constructBuildings($dominion->refresh(), $config, $totalLand);
        } catch (GameException $e) {
            // Shrivel like the cold mist, like the winds go wailing,
        }

        // Military
        try {
            $this->trainMilitary($dominion->refresh(), $config, $totalLand);
        } catch (GameException $e) {
            // Out into the barren lands far beyond the mountains!
        }

        // Explore
        try {
            if ($incomingLand < 120 && $totalLand < $config['max_land']) {
                $this->exploreLand($dominion->refresh(), $config, $totalLand);
            }
        } catch (GameException $e) {
            // Come never here again! Leave your barrow empty!
        }

        // Improvements
        try {
            $this->investCastle($dominion, $config);
        } catch (GameException $e) {
            // Lost and forgotten be, darker than the darkness,
        }

        // Release
        try {
            $this->releaseDraftees($dominion, $config);
        } catch (GameException $e) {
            // Where gates stand for ever shut, till the world is mended.
        }

        // Elite Guard
        if (isset($config['elite_guard_land']) && $config['elite_guard_land'] < $totalLand) {
            $dominion->elite_guard_active_at = now();
            $dominion->save();
        }
    }

    public function castSpells(Dominion $dominion, array $config) {
        foreach ($config['spells'] as $spellKey) {
            $spell = Spell::firstWhere('key', $spellKey);
            $spellDuration = $this->spellCalculator->getSpellDurationRemaining($dominion, $spell);
            if ($spellDuration == null || $spellDuration < 4) {
                $this->spellActionService->castSpell($dominion, $spellKey);
            }
        }
    }

    public function constructBuildings(Dominion $dominion, array $config, int $totalLand) {
        // TODO: calcuate actual percentages needed for farms, towers, etc
        $buildingsToConstruct = [];
        $maxAfford = $this->constructionCalculator->getMaxAfford($dominion);
        $barrenLand = $this->landCalculator->getBarrenLandByLandType($dominion);
        foreach ($config['build'] as $command) {
            if ($maxAfford > 0) {
                $buildingCount = (
                    $dominion->{'building_' . $command['building']}
                    + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_' . $command['building'])
                );
                $buildingPercentage = $buildingCount / $totalLand;

                if ($barrenLand[$command['land_type']] > 0) {
                    if ($command['amount'] == -1) {
                        // Unlimited
                        if ($command['building'] == 'home' && $this->populationCalculator->getEmploymentPercentage($dominion) < 100) {
                            // Check employment
                            continue;
                        }
                        $buildingsToConstruct['building_' . $command['building']] = min($maxAfford, $barrenLand[$command['land_type']]);
                    } elseif ($command['amount'] < 1 && $buildingPercentage < $command['amount']) {
                        // Percentage based
                        $buildingsToConstruct['building_' . $command['building']] = min($maxAfford, $barrenLand[$command['land_type']], ceil(($command['amount'] - $buildingPercentage) * $totalLand));
                    } else {
                        // Limited
                        if ($buildingCount < $command['amount']) {
                            $buildingsToConstruct['building_' . $command['building']] = min($maxAfford, $barrenLand[$command['land_type']], $command['amount'] - $buildingCount);
                        } else {
                            continue;
                        }
                    }
                    $maxAfford -= $buildingsToConstruct['building_' . $command['building']];
                    $barrenLand[$command['land_type']] -= $buildingsToConstruct['building_' . $command['building']];
                }
            }
        }

        if (!empty($buildingsToConstruct)) {
            $this->constructActionService->construct($dominion, $buildingsToConstruct);
        }
    }

    public function exploreLand(Dominion $dominion, array $config, int $totalLand) {
        // TODO: calcuate actual percentages needed for farms, towers, etc
        $landToExplore = [];
        $maxAfford = min($this->explorationCalculator->getMaxAfford($dominion), 24);
        foreach ($config['build'] as $command) {
            if ($maxAfford > 0) {
                $buildingCount = (
                    $dominion->{'building_' . $command['building']}
                    + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_' . $command['building'])
                    + $this->queueService->getExplorationQueueTotalByResource($dominion, 'land_' . $command['land_type'])
                );
                $buildingPercentage = $buildingCount / $totalLand;

                if ($command['amount'] == -1) {
                    // Unlimited
                    if ($command['building'] == 'home' && $this->populationCalculator->getEmploymentPercentage($dominion) < 100) {
                        // Check employment
                        continue;
                    }
                    $landToExplore['land_' . $command['land_type']] = $maxAfford;
                } elseif ($command['amount'] < 1 && $buildingPercentage < $command['amount']) {
                    // Percentage based
                    $landToExplore['land_' . $command['land_type']] = min($maxAfford, ceil(($command['amount'] - $buildingPercentage) * $totalLand));
                } else {
                    // Limited
                    if ($buildingCount < $command['amount']) {
                        $landToExplore['land_' . $command['land_type']] = min($maxAfford, $command['amount'] - $buildingCount);
                    } else {
                        continue;
                    }
                }
                $maxAfford -= $landToExplore['land_' . $command['land_type']];
            }
        }

        if (!empty($landToExplore)) {
            $this->exploreActionService->explore($dominion, $landToExplore);
        }
    }

    public function trainMilitary(Dominion $dominion, array $config, int $totalLand) {
        // TODO: check neighboring dominions?
        $defense = $this->militaryCalculator->getDefensivePower($dominion);
        $trainingQueue = $this->queueService->getTrainingQueueByPrefix($dominion, 'military_unit');
        $incomingTroops = $trainingQueue
            ->mapToGroups(function ($queue) {
                return [str_replace('military_unit', '', $queue->resource) => $queue->amount];
            })
            ->map(function ($unitType) {
                return $unitType->sum();
            })
            ->toArray();
        $incomingDefense = $this->militaryCalculator->getDefensivePower($dominion, null, null, $incomingTroops, 0, true, true);
        foreach ($config['military'] as $command) {
            $maxAfford = 0;
            if ($command['unit'] == 'spies') {
                // Train spies
                $spyRatio = $this->militaryCalculator->getSpyRatio($dominion, 'defense');
                $targetRatio = min(35, $dominion->round->daysInRound()) * $command['amount'];
                if ($spyRatio < $targetRatio) {
                    $incomingSpies = $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_spies');
                    if ($incomingSpies == 0) {
                        $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']];
                        $maxAfford = min(5, $maxAfford);
                    }
                }
            } elseif ($command['unit'] == 'wizards') {
                // Train wizards
                $wizardRatio = $this->militaryCalculator->getWizardRatio($dominion, 'defense');
                $targetRatio = min(35, $dominion->round->daysInRound()) * $command['amount'];
                if ($wizardRatio < $targetRatio) {
                    $incomingWizards = $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_spies');
                    if ($incomingWizards == 0) {
                        $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']];
                        $maxAfford = min(5, $maxAfford);
                    }
                }
            } else {
                // Train military
                $defenseRequired = $this->aiHelper->getDefenseForNonPlayer($dominion->round, $totalLand);
                if (($defense + $incomingDefense) < $defenseRequired) {
                    $maxAfford = $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']];
                }
            }
            if ($maxAfford > 0) {
                $this->trainActionService->train($dominion, ['military_' . $command['unit'] => $maxAfford]);
            }
        }
    }

    public function investCastle(Dominion $dominion, array $config) {
        if ($dominion->{'resource_' . $config['invest']} > 0) {
            $foodProduction = $this->productionCalculator->getFoodNetChange($dominion);
            if ($foodProduction < 0) {
                $this->improveActionService->improve($dominion, $config['invest'], ['harbor' => $dominion->{'resource_' . $config['invest']}]);
            } else {
                $sciencePercentage = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'science');
                $keepPercentage = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'keep');
                $wallsPercentage = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');
                if ($keepPercentage < 0.15) {
                    $this->improveActionService->improve($dominion, $config['invest'], ['keep' => $dominion->{'resource_' . $config['invest']}]);
                } elseif ($sciencePercentage < 0.08) {
                    $this->improveActionService->improve($dominion, $config['invest'], ['science' => $dominion->{'resource_' . $config['invest']}]);
                } elseif ($wallsPercentage < 0.10) {
                    $this->improveActionService->improve($dominion, $config['invest'], ['walls' => $dominion->{'resource_' . $config['invest']}]);
                } else {
                    $this->improveActionService->improve($dominion, $config['invest'], ['keep' => $dominion->{'resource_' . $config['invest']}]);
                }
            }
        }
    }

    public function releaseDraftees(Dominion $dominion, array $config) {
        $amount = $dominion->military_draftees;
        if ($dominion->resource_platinum > 200000) {
            // Keep draftees in reserve if unable to spend platinum
            $amount = max(0, $dominion->military_draftees - 800);
        }
        if ($amount > 0) {
            $this->releaseActionService->release($dominion, ['draftees' => $amount]);
        }
    }
}
