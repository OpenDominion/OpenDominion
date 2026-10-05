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
use OpenDominion\Calculators\Dominion\RangeCalculator;
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
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\InvasionService;
use OpenDominion\Services\Dominion\QueueService;
use RuntimeException;

class AIService
{
    /**
     * Bot defense goals include troops still in training, so the pre-invasion check compares
     * against the goal from this many hours ago to approximate defense actually at home.
     */
    protected const INVASION_CHECK_HOURS_BEHIND = 12;

    /**
     * Minutes of the hour attackers may invade, avoiding the tick (:00) and the hourly AI run (:30).
     * Must match the game:ai:invade schedule in the console kernel.
     */
    public const INVASION_MINUTES = [5, 10, 15, 20, 25, 35, 40, 45, 50, 55];

    /**
     * Draftees an attacker may hold before releasing down to ATTACKER_DRAFTEES_TARGET.
     */
    protected const ATTACKER_DRAFTEES_MAX = 2000;

    /**
     * Draftees an attacker keeps in reserve after releasing the surplus.
     */
    protected const ATTACKER_DRAFTEES_TARGET = 1500;

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

    /** @var InvasionService */
    protected $invasionService;

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

    /** @var RangeCalculator */
    protected $rangeCalculator;

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
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->rezoningCalculator = app(RezoningCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->trainingCalculator = app(TrainingCalculator::class);

        // Helpers
        $this->aiHelper = app(AIHelper::class);

        // Services
        $this->invasionService = app(InvasionService::class);
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

    /**
     * Attempts invasions for attacking non-player dominions whose invasion minute is the current minute.
     */
    public function executeInvasions(?Carbon $now = null): void
    {
        $now = $now ?? now();

        foreach (Round::active()->get() as $round) {
            $this->executeRoundInvasions($round, $now);
        }
    }

    /**
     * Attempts invasions for a round's attacking non-player dominions whose invasion minute is the given minute.
     *
     * Only attackers slotted for this minute are loaded; the slot is computed in the query to match getInvasionMinute().
     */
    public function executeRoundInvasions(Round $round, Carbon $now): void
    {
        $slot = array_search($now->minute, static::INVASION_MINUTES, true);
        if ($slot === false) {
            return;
        }

        $dominions = $round->activeDominions()
            ->where('dominions.ai_enabled', true)
            ->whereNull('dominions.user_id')
            ->where('dominions.ai_config', 'like', '%"strategy":"' . AIHelper::STRATEGY_ATTACKER . '"%')
            ->whereRaw('MOD(CRC32(CONCAT(dominions.id, ?)), ?) = ?', [
                $this->getInvasionSeedSuffix($now),
                count(static::INVASION_MINUTES),
                $slot,
            ])
            ->with([
                'queues',
                'race',
                'realm',
                'round',
            ])
            ->get();

        foreach ($dominions as $dominion) {
            try {
                app(RoundMutationService::class)->runForDominion($dominion, function (Dominion $dominion): void {
                    if ($dominion->locked_at === null && $dominion->ai_enabled) {
                        $this->attemptInvasion($dominion, $dominion->ai_config);
                    }
                });
            } catch (Exception $e) {
                continue;
            }
        }
    }

    /**
     * Returns the minute of the given hour an attacker tries to invade, varying by dominion and hour.
     */
    public function getInvasionMinute(Dominion $dominion, Carbon $datetime): int
    {
        $seed = crc32($dominion->id . $this->getInvasionSeedSuffix($datetime));

        return static::INVASION_MINUTES[$seed % count(static::INVASION_MINUTES)];
    }

    protected function getInvasionSeedSuffix(Carbon $datetime): string
    {
        return '-' . $datetime->copy()->startOfHour()->timestamp;
    }

    public function performActions(Dominion $dominion): void
    {
        app(RoundMutationService::class)->runForDominion($dominion, function (Dominion $dominion): void {
            if ($dominion->locked_at !== null || !$dominion->ai_enabled) {
                return;
            }
            $this->performLockedActions($dominion);
        });
    }

    protected function performLockedActions(Dominion $dominion): void
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
                                $this->changeDraftRateActionService->changeDraftRate($dominion, clamp($instruction['amount'], 0, 90));
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

        if (($config['strategy'] ?? AIHelper::STRATEGY_EXPLORER) === AIHelper::STRATEGY_ATTACKER) {
            $this->performAttackerActions($dominion, $config);
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

        $this->joinEliteGuard($dominion, $config, $totalLand);
    }

    /**
     * Performs actions for a non-player dominion that trains offense and invades other bots instead of exploring.
     */
    public function performAttackerActions(Dominion $dominion, array $config): void
    {
        $config = $this->applyUnitSwap($dominion, $config);

        // Spells
        try {
            $this->castSpells($dominion, $config);
        } catch (GameException $e) {
            // Blood and thunder, steel and fire,
        }

        $totalLand = $this->landCalculator->getTotalLandIncoming($dominion->refresh());

        // Rezone
        try {
            $this->rezoneForBuildPlan($dominion->refresh(), $config, $totalLand);
        } catch (GameException $e) {
            // Turn the soil where the fallen lie,
        }

        // Construction
        try {
            $this->constructBuildings($dominion->refresh(), $config, $totalLand);
        } catch (GameException $e) {
            // Raise the halls upon their bones,
        }

        // Military
        try {
            $this->trainAttackerMilitary($dominion->refresh(), $config, $totalLand);
        } catch (GameException $e) {
            // Sound the horns and fill the ranks,
        }

        // Improvements
        try {
            $this->investCastle($dominion->refresh(), $config);
        } catch (GameException $e) {
            // Gild the keep and whet the walls,
        }

        // Release
        try {
            $this->releaseSurplusDraftees($dominion->refresh());
        } catch (GameException $e) {
            // Till the last of the living march home.
        }

        $this->joinEliteGuard($dominion, $config, $totalLand);
    }

    protected function joinEliteGuard(Dominion $dominion, array $config, int $totalLand): void
    {
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
                        $buildingsToConstruct['building_' . $command['building']] = min($maxAfford, $barrenLand[$command['land_type']], rceil(($command['amount'] - $buildingPercentage) * $totalLand));
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
                    $landToExplore['land_' . $command['land_type']] = min($maxAfford, rceil(($command['amount'] - $buildingPercentage) * $totalLand));
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
            if (in_array($command['unit'], ['spies', 'wizards'])) {
                $maxAfford = $this->getOperativesToTrain($dominion, $command);
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

    /**
     * Returns the number of spies or wizards to train for a military instruction.
     */
    protected function getOperativesToTrain(Dominion $dominion, array $command): int
    {
        if ($command['unit'] == 'spies') {
            $ratio = $this->militaryCalculator->getSpyRatio($dominion, 'defense');
        } else {
            $ratio = $this->militaryCalculator->getWizardRatio($dominion, 'defense');
        }

        $targetRatio = min(35, $dominion->round->daysInRound()) * $command['amount'];
        if ($ratio >= $targetRatio) {
            return 0;
        }

        $incoming = $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_' . $command['unit']);
        if ($incoming > 0) {
            return 0;
        }

        return min(5, $this->trainingCalculator->getMaxTrainable($dominion)[$command['unit']]);
    }

    /**
     * Switches an attacker's unit composition once it reaches the configured prestige.
     */
    public function applyUnitSwap(Dominion $dominion, array $config): array
    {
        if (!isset($config['unit_swap']) || $dominion->prestige < $config['unit_swap']['prestige']) {
            return $config;
        }

        $config['military'][0]['unit'] = $config['unit_swap']['military'];
        $config['offense'] = $config['unit_swap']['offense'];
        unset($config['unit_swap']);

        $dominion->ai_config = $config;
        $dominion->save();

        return $config;
    }

    /**
     * Returns defensive power from units that never leave on invasions, including those in training.
     */
    public function getHomeGuardDefense(Dominion $dominion): float
    {
        $offensiveSlots = $this->aiHelper->getAttackerOffensiveSlots($dominion->race);

        $units = [0 => $dominion->military_draftees];
        for ($slot = 1; $slot <= 4; $slot++) {
            if (in_array($slot, $offensiveSlots)) {
                $units[$slot] = 0;
                continue;
            }

            $units[$slot] = $dominion->{"military_unit{$slot}"}
                + $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
        }

        return $this->militaryCalculator->getDefensivePower($dominion, null, null, $units, 0, false, true);
    }

    /**
     * Trains defense until the home guard meets bot defense requirements, then spends everything on offense.
     */
    public function trainAttackerMilitary(Dominion $dominion, array $config, int $totalLand): void
    {
        $defenseRequired = $this->aiHelper->getDefenseForNonPlayer($dominion->round, $totalLand);

        foreach ($config['military'] as $index => $command) {
            if (in_array($command['unit'], ['spies', 'wizards'])) {
                $amount = $this->getOperativesToTrain($dominion, $command);
            } else {
                $unit = $command['unit'];
                if ($index === 0 && $this->getHomeGuardDefense($dominion) >= $defenseRequired) {
                    $unit = $config['offense'];
                }
                $amount = $this->trainingCalculator->getMaxTrainable($dominion)[$unit];
                $command['unit'] = $unit;
            }

            if ($amount > 0) {
                $this->trainActionService->train($dominion, ['military_' . $command['unit'] => $amount]);
            }
        }
    }

    /**
     * Returns offensive units at home that can be sent, limited by available boats.
     *
     * @return array<int, int>
     */
    public function getAvailableOffensiveUnits(Dominion $dominion): array
    {
        $boatSpace = $dominion->resource_boats * $this->militaryCalculator->getBoatCapacity($dominion);

        $units = [];
        foreach ($this->aiHelper->getAttackerOffensiveSlots($dominion->race) as $slot) {
            $unit = $dominion->race->units->firstWhere('slot', $slot);
            if ($unit === null || $unit->power_offense <= 0) {
                continue;
            }

            $amount = (int) $dominion->{"military_unit{$slot}"};
            if ($this->militaryCalculator->getUnitNeedBoats($dominion, $unit)) {
                $amount = (int) min($amount, $boatSpace);
                $boatSpace -= $amount;
            }

            $units[$slot] = $amount;
        }

        return $units;
    }

    /**
     * Invades the largest bot in range that can be broken, sending the smallest force that succeeds.
     *
     * @return bool Whether an invasion was made
     */
    public function attemptInvasion(Dominion $dominion, array $config): bool
    {
        if ($dominion->realm->number != 0 || $dominion->round->hasOffensiveActionsDisabled()) {
            return false;
        }

        if (!$this->invasionService->hasEnoughMorale($dominion)) {
            return false;
        }

        $totalLand = $this->landCalculator->getTotalLand($dominion);
        if ($totalLand >= $config['max_land']) {
            return false;
        }

        // Wait for the previous army to return home
        if ($this->queueService->getInvasionQueueTotalByPrefix($dominion, 'military_unit') > 0) {
            return false;
        }

        $availableUnits = $this->getAvailableOffensiveUnits($dominion);
        if (array_sum($availableUnits) === 0) {
            return false;
        }

        // Don't look at real targets until we could break a typical bot at minimum range
        // Skipped on day 1, when freshly spawned bots are far below the defense formula
        $minRange = $config['min_range'] ?? 75;
        if ($dominion->round->daysInRound() > 1) {
            $expectedDefense = $this->aiHelper->getDefenseForNonPlayer(
                $dominion->round,
                (int) floor($totalLand * $minRange / 100),
                now()->subHours(static::INVASION_CHECK_HOURS_BEHIND)
            );
            $maxOffense = $this->militaryCalculator->getOffensivePower($dominion, null, $minRange / 100, $availableUnits);
            if ($maxOffense <= $expectedDefense) {
                return false;
            }
        }

        $targets = $this->rangeCalculator->getDominionsInRange($dominion, false, true)
            ->filter(function (Dominion $target) use ($dominion, $minRange) {
                return $target->user_id === null
                    && $target->realm_id === $dominion->realm_id
                    && $this->rangeCalculator->getDominionRange($dominion, $target) >= $minRange;
            });

        if ($targets->isEmpty()) {
            return false;
        }

        $this->castAttackSpells($dominion, $config);

        foreach ($targets as $target) {
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
            $targetDefense = $this->militaryCalculator->getDefensivePowerWithTemples($dominion, $target);

            $units = $this->getUnitsToSend($dominion, $target, $landRatio, $availableUnits, $targetDefense);
            if ($units === null) {
                continue;
            }

            if (
                !$this->invasionService->hasEnoughBoats($dominion, $units)
                || !$this->invasionService->passes40PercentRule($dominion, $target, $units)
                || !$this->invasionService->passes54RatioRule($dominion, $target, $landRatio, $units)
            ) {
                continue;
            }

            try {
                app(InvadeActionService::class)->invade($dominion, $target, $units, true);
                return true;
            } catch (GameException $e) {
                $dominion->refresh();
                continue;
            }
        }

        return false;
    }

    /**
     * Casts racial attack spells that aren't already active, right before looking for an invasion target.
     */
    public function castAttackSpells(Dominion $dominion, array $config): void
    {
        $spellCast = false;
        foreach ($config['attack_spells'] ?? [] as $spellKey) {
            if ($this->spellCalculator->isSpellActive($dominion, $spellKey)) {
                continue;
            }

            try {
                $this->spellActionService->castSpell($dominion, $spellKey);
                $spellCast = true;
            } catch (GameException $e) {
                // Attack without it
            }
        }

        if ($spellCast) {
            $dominion->refresh();
        }
    }

    /**
     * Returns the smallest force whose offense exceeds the target's defense, or null if none can.
     *
     * Units that contribute the least defense per point of offense are sent first.
     *
     * @param array<int, int> $availableUnits
     * @return array<int, int>|null
     */
    public function getUnitsToSend(Dominion $dominion, Dominion $target, float $landRatio, array $availableUnits, float $targetDefense): ?array
    {
        $slots = collect($availableUnits)
            ->filter()
            ->keys()
            ->sortBy(function (int $slot) use ($dominion, $target, $landRatio) {
                $unit = $dominion->race->units->firstWhere('slot', $slot);
                $offense = $this->militaryCalculator->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'offense');
                $defense = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $unit, 'defense');

                return $offense > 0 ? ($defense / $offense) : PHP_INT_MAX;
            });

        $units = [];
        foreach ($slots as $slot) {
            $units[$slot] = $availableUnits[$slot];
            if ($this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units) <= $targetDefense) {
                continue;
            }

            // Binary search for the fewest units of this slot that still breaks the target
            $failing = 0;
            $passing = $availableUnits[$slot];
            while ($passing - $failing > 1) {
                $units[$slot] = intdiv($failing + $passing, 2);
                if ($this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units) > $targetDefense) {
                    $passing = $units[$slot];
                } else {
                    $failing = $units[$slot];
                }
            }
            $units[$slot] = $passing;

            return array_filter($units);
        }

        return null;
    }

    /**
     * Rezones barren land that the build plan won't use toward land types it still needs.
     */
    public function rezoneForBuildPlan(Dominion $dominion, array $config, int $totalLand): void
    {
        $barrenLand = $this->landCalculator->getBarrenLandByLandType($dominion);
        $landNeeded = array_fill_keys($this->landHelper->getLandTypes(), 0);
        $unlimitedLandTypes = [];
        $overflowLandType = null;

        foreach ($config['build'] as $command) {
            if ($command['amount'] == -1) {
                // Unlimited buildings absorb any leftover land, homes only while fully employed
                $unlimitedLandTypes[] = $command['land_type'];
                if ($overflowLandType === null && !($command['building'] == 'home' && $this->populationCalculator->getEmploymentPercentage($dominion) < 100)) {
                    $overflowLandType = $command['land_type'];
                }
                continue;
            }

            $buildingCount = (
                $dominion->{'building_' . $command['building']}
                + $this->queueService->getConstructionQueueTotalByResource($dominion, 'building_' . $command['building'])
            );
            $buildingTarget = $command['amount'] < 1 ? rceil($command['amount'] * $totalLand) : $command['amount'];
            $landNeeded[$command['land_type']] += max(0, $buildingTarget - $buildingCount);
        }

        $surplus = [];
        $deficit = [];
        foreach ($barrenLand as $landType => $barren) {
            if ($landNeeded[$landType] > $barren) {
                $deficit[$landType] = $landNeeded[$landType] - $barren;
            } elseif (!in_array($landType, $unlimitedLandTypes) && $barren > $landNeeded[$landType]) {
                $surplus[$landType] = $barren - $landNeeded[$landType];
            }
        }

        $rezoneTotal = min(array_sum($surplus), $this->rezoningCalculator->getMaxAfford($dominion));
        if ($overflowLandType === null) {
            $rezoneTotal = min($rezoneTotal, array_sum($deficit));
        }
        if ($rezoneTotal <= 0) {
            return;
        }

        $landToRemove = $this->allocateLand($surplus, $rezoneTotal);
        $landToAdd = $this->allocateLand($deficit, $rezoneTotal);
        $leftover = $rezoneTotal - array_sum($landToAdd);
        if ($leftover > 0) {
            $landToAdd[$overflowLandType] = ($landToAdd[$overflowLandType] ?? 0) + $leftover;
        }

        $this->rezoneActionService->rezone($dominion, $landToRemove, $landToAdd);
    }

    /**
     * Takes up to $total acres from land types in order.
     *
     * @param array<string, int> $available
     * @return array<string, int>
     */
    protected function allocateLand(array $available, int $total): array
    {
        $allocated = [];
        foreach ($available as $landType => $amount) {
            if ($total <= 0) {
                break;
            }
            $allocated[$landType] = min($amount, $total);
            $total -= $allocated[$landType];
        }

        return $allocated;
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

    /**
     * Releases draftees an attacker has stockpiled beyond what it can spend on training.
     */
    public function releaseSurplusDraftees(Dominion $dominion): void
    {
        if ($dominion->military_draftees <= static::ATTACKER_DRAFTEES_MAX) {
            return;
        }

        $amount = $dominion->military_draftees - static::ATTACKER_DRAFTEES_TARGET;
        $this->releaseActionService->release($dominion, ['draftees' => $amount]);
    }
}
