<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InvasionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class InvadeActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Base percentage of boats sunk
     */
    protected const BOATS_SUNK_BASE_PERCENTAGE = 5;

    /**
     * @var float Base percentage of defensive casualties
     */
    protected const CASUALTIES_DEFENSIVE_BASE_PERCENTAGE = 3.6;

    /**
     * @var float Max percentage of defensive casualties
     */
    protected const CASUALTIES_DEFENSIVE_MAX_PERCENTAGE = 4.8;

    /**
     * @var float Base percentage of offensive casualties
     */
    protected const CASUALTIES_OFFENSIVE_BASE_PERCENTAGE = 8.5;

    /**
     * @var float Failing an invasion by this percentage (or more) results in 'being overwhelmed'
     */
    protected const OVERWHELMED_PERCENTAGE = 20.0;

    /**
     * @var float Used to cap prestige gain formula
     */
    protected const PRESTIGE_CAP = 60;

    /**
     * @var int Land ratio multiplier for prestige when invading successfully
     */
    protected const PRESTIGE_RANGE_MULTIPLIER = 100;

    /**
     * @var int Base prestige when invading successfully
     */
    protected const PRESTIGE_CHANGE_BASE = -40;

    /**
     * @var float Base prestige % change for both parties when invading
     */
    protected const PRESTIGE_LOSS_PERCENTAGE = 5.0;

    /**
     * @var float Additional prestige % change for defender from recent invasions
     */
    protected const PRESTIGE_LOSS_PERCENTAGE_PER_INVASION = 1.0;

    /**
     * @var float Maximum prestige % change for defender
     */
    protected const PRESTIGE_LOSS_PERCENTAGE_CAP = 15.0;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var InvasionService */
    protected $invasionService;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    // todo: use InvasionRequest class with op, dp, mods etc etc. Since now it's
    // a bit hacky with getting new data between $dominion/$target->save()s

    /** @var array Invasion result array. todo: Should probably be refactored later to its own class */
    protected $invasionResult = [
        'result' => [],
        'attacker' => [
            'unitsLost' => [],
            'unitsSent' => [],
        ],
        'defender' => [
            'unitsLost' => [],
        ],
    ];

    // todo: refactor
    /** @var GameEvent */
    protected $invasionEvent;

    // todo: refactor to use $invasionResult instead
    /** @var int The amount of land lost during the invasion */
    protected $landLost = 0;

    /** @var int The amount of units lost during the invasion */
    protected $unitsLost = 0;

    /**
     * InvadeActionService constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param CasualtiesCalculator $casualtiesCalculator
     * @param GovernmentService $governmentService
     * @param InvasionService $invasionService
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param NotificationService $notificationService
     * @param ProtectionService $protectionService
     * @param QueueService $queueService
     * @param RangeCalculator $rangeCalculator
     * @param SpellCalculator $spellCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        CasualtiesCalculator $casualtiesCalculator,
        GovernmentService $governmentService,
        InvasionService $invasionService,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        NotificationService $notificationService,
        ProtectionService $protectionService,
        QueueService $queueService,
        RangeCalculator $rangeCalculator,
        SpellCalculator $spellCalculator
    )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->casualtiesCalculator = $casualtiesCalculator;
        $this->governmentService = $governmentService;
        $this->invasionService = $invasionService;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->notificationService = $notificationService;
        $this->protectionService = $protectionService;
        $this->queueService = $queueService;
        $this->rangeCalculator = $rangeCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Invades dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws GameException
     */
    public function invade(Dominion $dominion, Dominion $target, array $units, ?bool $cancel_leave_range): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardLockedDominion($target);
        $this->guardActionsDuringTick($dominion, 5);

        DB::transaction(function () use ($dominion, $target, $units, $cancel_leave_range) {
            if ($dominion->round->hasOffensiveActionsDisabled()) {
                throw new GameException('Invasions have been disabled for the remainder of the round');
            }

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot invade while under protection');
            }

            if ($this->protectionService->isUnderProtection($target)) {
                throw new GameException('You cannot invade dominions which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target)) {
                throw new GameException('You cannot invade dominions outside of your range');
            }

            if ($dominion->round->id !== $target->round->id) {
                throw new GameException('Nice try, but you cannot invade cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new GameException('Nice try, but you cannot invade your realmies');
            }

            // Sanitize input
            $units = array_map('intval', array_filter($units));

            $range = $this->rangeCalculator->getDominionRange($dominion, $target);
            $this->invasionResult['result']['range'] = $range;
            $landRatio = $range / 100;

            if ($cancel_leave_range === true && $range < 75) {
                throw new GameException('Your attack was canceled because the target is no longer in your 75% range');
            }

            if (!$this->invasionService->hasAnyOP($dominion, $units)) {
                throw new GameException('You need to send at least some units');
            }

            if (!$this->invasionService->allUnitsHaveOP($dominion, $units)) {
                throw new GameException('You cannot send units that have no OP');
            }

            if (!$this->invasionService->hasEnoughUnitsAtHome($dominion, $units)) {
                throw new GameException('You don\'t have enough units at home to send this many units');
            }

            if (!$this->invasionService->hasEnoughBoats($dominion, $units)) {
                throw new GameException('You do not have enough boats to send this many units');
            }

            if (!$this->invasionService->hasEnoughMorale($dominion)) {
                throw new GameException('You do not have enough morale to invade others');
            }

            if (!$this->invasionService->passes33PercentRule($dominion, $target, $units)) {
                throw new GameException('You need to leave more DP units at home (33% rule)');
            }

            if (!$this->invasionService->passes54RatioRule($dominion, $target, $landRatio, $units)) {
                throw new GameException('You are sending out too much OP, based on your new home DP (5:4 rule)');
            }

            foreach($units as $amount) {
                if ($amount < 0) {
                    throw new GameException('Invasion was canceled due to bad input');
                }
            }

            // Handle invasion results
            $this->checkInvasionSuccess($dominion, $target, $units);
            $this->checkOverwhelmed();

            $this->rangeCalculator->checkGuardApplications($dominion, $target);

            $this->invasionResult['attacker']['repeatInvasion'] = $this->militaryCalculator->getRecentlyInvadedCount($target, 8, true, $dominion) > 1;
            $this->invasionResult['defender']['recentlyInvadedCount'] = $this->militaryCalculator->getRecentlyInvadedCount($target);
            $this->handleBoats($dominion, $target, $units);
            $this->handlePrestigeChanges($dominion, $target, $units);
            $this->handleDefensiveCasualties($dominion, $target, $units);

            $survivingUnits = $this->handleOffensiveCasualties($dominion, $target, $units);
            $this->handleAfterInvasionUnitPerks($dominion, $target, $survivingUnits);
            $this->handleResearchPoints($dominion, $target, $survivingUnits);

            $convertedUnits = $this->handleConversions($dominion, $target, $units, $survivingUnits);
            $this->handleReturningUnits($dominion, $survivingUnits, $convertedUnits);

            $this->handleMoraleChanges($dominion, $target);
            $this->handleLandGrabs($dominion, $target);

            $this->invasionResult['attacker']['unitsSent'] = $units;

            // Stat changes
            // todo: move to own method
            if ($this->invasionResult['result']['success']) {
                $dominion->stat_total_land_conquered += (int)array_sum($this->invasionResult['attacker']['landConquered']);
                $dominion->stat_total_land_conquered += (int)array_sum($this->invasionResult['attacker']['landGenerated']);
                $target->stat_total_land_lost += (int)array_sum($this->invasionResult['attacker']['landConquered']);
                if ($range >= 75) {
                    $dominion->stat_attacking_success += 1;
                    $target->stat_defending_failure += 1;
                }
            } else {
                $target->stat_defending_success += 1;
                $dominion->stat_attacking_failure += 1;
            }

            // todo: move to GameEventService
            $this->invasionEvent = GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => Dominion::class,
                'target_id' => $target->id,
                'type' => 'invasion',
                'data' => $this->invasionResult,
            ]);

            // todo: move to its own method
            // Notification
            if ($this->invasionResult['result']['success']) {
                $this->notificationService->queueNotification('received_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $dominion->id,
                    'landLost' => $this->landLost,
                    'unitsLost' => $this->unitsLost,
                ]);
            } else {
                $this->notificationService->queueNotification('repelled_invasion', [
                    '_routeParams' => [(string)$this->invasionEvent->id],
                    'attackerDominionId' => $dominion->id,
                    'attackerWasOverwhelmed' => $this->invasionResult['result']['overwhelmed'],
                    'unitsLost' => $this->unitsLost,
                ]);
            }

            $dominion->resetAbandonment();
            if ($this->invasionResult['result']['success']) {
                $target->resetAbandonment(12);
            }

            $dominion->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
            $target->save(['event' => HistoryService::EVENT_ACTION_INVADED]);
        });

        $this->notificationService->sendNotifications($target, 'irregular_dominion');

        if ($this->invasionResult['result']['success']) {
            $message = sprintf(
                'Your army fights valiantly, and defeats the forces of %s (#%s), conquering %s new acres of land! During the invasion, your troops also discovered %s acres of land.',
                $target->name,
                $target->realm->number,
                number_format(array_sum($this->invasionResult['attacker']['landConquered'])),
                number_format(array_sum($this->invasionResult['attacker']['landGenerated']))
            );
            $alertType = 'success';
        } else {
            $message = sprintf(
                'Your army fails to defeat the forces of %s (#%s).',
                $target->name,
                $target->realm->number
            );
            $alertType = 'danger';
        }

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->invasionEvent->id])
        ];
    }

    /**
     * Handles prestige changes for both dominions.
     *
     * Prestige gains and losses are based on several factors. The most
     * important one is the range (aka relative land size percentage) of the
     * target compared to the attacker.
     *
     * -   X -  65 equals a very weak target, and the attacker is penalized with a prestige loss, no matter the outcome
     * -  66 -  74 equals a weak target, and incurs no prestige changes for either side, no matter the outcome
     * -  75 - 119 equals an equal target, and gives full prestige changes, depending on if the invasion is successful
     * - 120 - X   equals a strong target, and incurs no prestige changes for either side, no matter the outcome
     *
     * Due to the above, people are encouraged to hit targets in 75-119 range,
     * and are discouraged to hit anything below 66.
     *
     * Failing an attack above 66% range only results in a prestige loss if the
     * attacker is overwhelmed by the target defenses.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
    protected function handlePrestigeChanges(Dominion $dominion, Dominion $target, array $units): void
    {
        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        $isOverwhelmed = $this->invasionResult['result']['overwhelmed'];
        $range = $this->invasionResult['result']['range'];

        $attackerPrestigeChange = 0;
        $targetPrestigeChange = 0;
        $multiplier = 1;

        if ($isOverwhelmed || ($range < 60)) {
            $attackerPrestigeChange = ($dominion->prestige * -(static::PRESTIGE_LOSS_PERCENTAGE / 100));
        } elseif ($isInvasionSuccessful && ($range >= 75)) {
            $attackerPrestigeChange = min(
                static::PRESTIGE_RANGE_MULTIPLIER * ($range / 100) + static::PRESTIGE_CHANGE_BASE, // Gained through invading
                static::PRESTIGE_CAP // But capped at 100%
            ) + ($this->landCalculator->getTotalLand($target) / 250); // Bonus for land size of target

            $weeklyInvadedCount = $this->militaryCalculator->getRecentlyInvadedCount($target, 24 * 7, true);
            $prestigeLossPercentage = min(
                (static::PRESTIGE_LOSS_PERCENTAGE / 100) + (static::PRESTIGE_LOSS_PERCENTAGE_PER_INVASION / 100 * $weeklyInvadedCount),
                (static::PRESTIGE_LOSS_PERCENTAGE_CAP / 100)
            );
            $targetPrestigeChange = (int)round($target->prestige * -($prestigeLossPercentage));

            // Racial Bonus
            $multiplier += $dominion->race->getPerkMultiplier('prestige_gains');

            // Techs
            $multiplier += $dominion->getTechPerkMultiplier('prestige_gains');

            // Wonders
            $multiplier += $dominion->getWonderPerkMultiplier('prestige_gains');

            // War Bonus
            if ($this->governmentService->isMutualWarEscalated($dominion->realm, $target->realm)) {
                $multiplier += 0.2;
            } elseif ($this->governmentService->isWarEscalated($dominion->realm, $target->realm) || $this->governmentService->isWarEscalated($target->realm, $dominion->realm)) {
                $multiplier += 0.1;
            }

            $attackerPrestigeChange *= $multiplier;

            // Penalty for habitual invasions
            $habitualHits = $this->militaryCalculator->getHabitualInvasionCount($dominion, $target);
            if ($target->user_id == null) {
                // Penalty for bots
                $penalty = 0.10;
                $penaltyHits = max(0, $habitualHits - 3);
            } else {
                // Penalty for human players
                $penalty = 0.10;
                $penaltyHits = max(0, $habitualHits - 1);
            }
            $this->invasionResult['attacker']['habitualInvasion'] = $penaltyHits > 0;
            $attackerPrestigeChange *= max(0.50, (1 - $penalty * $penaltyHits));

            // Repeat Invasions award no prestige
            if ($this->invasionResult['attacker']['repeatInvasion']) {
                $attackerPrestigeChange = 0;
            }
        }

        $attackerPrestigeChange = (int)round($attackerPrestigeChange);
        if ($attackerPrestigeChange !== 0) {
            if (!$isInvasionSuccessful) {
                // Unsuccessful invasions (bounces) give negative prestige immediately
                $dominion->prestige += $attackerPrestigeChange;

            } else {
                // todo: possible bug if all 12hr units die (somehow) and only 9hr units survive, prestige gets returned after 12 hrs, since $units is input, not surviving units. fix?
                $slowestTroopsReturnHours = $this->invasionService->getSlowestUnitReturnHours($dominion, $units);
                $this->queueService->queueResources(
                    'invasion',
                    $dominion,
                    ['prestige' => $attackerPrestigeChange],
                    $slowestTroopsReturnHours
                );
            }

            $this->invasionResult['attacker']['prestigeChange'] = $attackerPrestigeChange;
        }

        if ($targetPrestigeChange !== 0) {
            $target->prestige += $targetPrestigeChange;

            $this->invasionResult['defender']['prestigeChange'] = $targetPrestigeChange;
        }
    }

    /**
     * Handles offensive casualties for the attacking dominion.
     *
     * Offensive casualties are 8.5% of the units needed to break the target,
     * regardless of how many you send.
     *
     * On unsuccessful invasions, offensive casualties are 8.5% of all units
     * you send, doubled if you are overwhelmed.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array All the units that survived and will return home
     */
    protected function handleOffensiveCasualties(Dominion $dominion, Dominion $target, array $units): array
    {
        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        $isOverwhelmed = $this->invasionResult['result']['overwhelmed'];
        $landRatio = $this->invasionResult['result']['range'] / 100;
        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];
        $offensiveCasualtiesPercentage = (static::CASUALTIES_OFFENSIVE_BASE_PERCENTAGE / 100);

        $offensiveUnitsLost = [];

        if ($isInvasionSuccessful) {
            $totalUnitsSent = array_sum($units);

            $averageOPPerUnitSent = ($attackingForceOP / $totalUnitsSent);
            $unitsNeededToBreakTarget = round($targetDP / $averageOPPerUnitSent);

            $totalUnitsLeftToKill = (int)ceil($unitsNeededToBreakTarget * $offensiveCasualtiesPercentage);

            foreach ($units as $slot => $amount) {
                $slotTotalAmountPercentage = ($amount / $totalUnitsSent);

                if ($slotTotalAmountPercentage === 0) {
                    continue;
                }

                $unitsToKill = ceil($unitsNeededToBreakTarget * $offensiveCasualtiesPercentage * $slotTotalAmountPercentage);
                $offensiveUnitsLost[$slot] = $unitsToKill;

                if ($totalUnitsLeftToKill < $unitsToKill) {
                    $unitsToKill = $totalUnitsLeftToKill;
                }

                $totalUnitsLeftToKill -= $unitsToKill;

                $fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties');
                if ($fixedCasualtiesPerk) {
                    $fixedCasualtiesRatio = $fixedCasualtiesPerk / 100;
                    $unitsActuallyKilled = (int)ceil($amount * $fixedCasualtiesRatio);
                    $offensiveUnitsLost[$slot] = $unitsActuallyKilled;
                }
            }
        } else {
            foreach ($units as $slot => $amount) {
                $fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties');
                if ($fixedCasualtiesPerk) {
                    $fixedCasualtiesRatio = $fixedCasualtiesPerk / 100;
                    $unitsToKill = (int)ceil($amount * $fixedCasualtiesRatio);
                    $offensiveUnitsLost[$slot] = $unitsToKill;
                    continue;
                }

                $unitsToKill = (int)ceil($amount * $offensiveCasualtiesPercentage);
                $offensiveUnitsLost[$slot] = $unitsToKill;
            }
        }

        foreach ($offensiveUnitsLost as $slot => &$amount) {
            // Reduce amount of units to kill by further multipliers
            $unitsToKillMultiplier = $this->casualtiesCalculator->getOffensiveCasualtiesMultiplierForUnitSlot($dominion, $target, $slot, $units, $landRatio, $isOverwhelmed);

            if ($unitsToKillMultiplier !== 1) {
                $amount = (int)ceil($amount * $unitsToKillMultiplier);
            }

            if ($amount > 0) {
                // Actually kill the units. RIP in peace, glorious warriors ;_;7
                $dominion->{"military_unit{$slot}"} -= $amount;

                $this->invasionResult['attacker']['unitsLost'][$slot] = $amount;
            }
        }
        unset($amount); // Unset var by reference from foreach loop above to prevent unintended side-effects

        $survivingUnits = $units;

        foreach ($units as $slot => $amount) {
            if (isset($offensiveUnitsLost[$slot])) {
                $survivingUnits[$slot] -= $offensiveUnitsLost[$slot];
            }
        }

        return $survivingUnits;
    }

    /**
     * Handles defensive casualties for the defending dominion.
     *
     * Defensive casualties are base 4.5% across all units that help defending.
     *
     * This scales with relative land size, and invading OP compared to
     * defending OP, up to max 6%.
     *
     * Unsuccessful invasions results in reduced defensive casualties, based on
     * the invading force's OP.
     *
     * Defensive casualties are spread out in ratio between all units that help
     * defend, including draftees. Being recently invaded reduces defensive
     * casualties: 100%, 80%, 60%, 55%, 45%, 35%.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return int
     */
    protected function handleDefensiveCasualties(Dominion $dominion, Dominion $target, array $units): int
    {
        if ($this->invasionResult['result']['overwhelmed'])
        {
            return 0;
        }

        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];
        $defensiveCasualtiesPercentage = (static::CASUALTIES_DEFENSIVE_BASE_PERCENTAGE / 100);

        if ($this->invasionResult['result']['success']) {
            // Modify casualties percentage based on relative land size
            $landRatio = $this->invasionResult['result']['range'] / 100;
            $defensiveCasualtiesPercentage *= clamp($landRatio, 0.4, 1);

            // Scale casualties further with invading OP vs target DP
            $defensiveCasualtiesPercentage *= ($attackingForceOP / $targetDP);

            // Cap max casualties
            $defensiveCasualtiesPercentage = min(
                $defensiveCasualtiesPercentage,
                (static::CASUALTIES_DEFENSIVE_MAX_PERCENTAGE / 100)
            );
        } else {
            // Raze casualties scale linearly from 0% at overwhelmed to 100% at OP == DP
            $minRatio = (100 - static::OVERWHELMED_PERCENTAGE) / 100;
            $steps = (100 / static::OVERWHELMED_PERCENTAGE);
            $defensiveCasualtiesPercentage *= (($attackingForceOP / $targetDP) - $minRatio) * $steps;
        }

        // Reduce casualties if target has been hit recently
        $recentlyInvadedCount = $this->invasionResult['defender']['recentlyInvadedCount'];

        if ($recentlyInvadedCount === 1) {
            $recentInvasionModifier = 0.75;
        } elseif ($recentlyInvadedCount === 2) {
            $recentInvasionModifier = 0.5;
        } elseif ($recentlyInvadedCount >= 3) {
            $recentInvasionModifier = 0.25;
        } else {
            $recentInvasionModifier = 1;
        }

        $defensiveUnitsLost = [];

        // Draftees
        if ($this->spellCalculator->isSpellActive($dominion, 'unholy_ghost')) {
            $drafteesLost = 0;
        } else {
            $finalCasualtiesPercentage = min(
                $recentInvasionModifier,
                $this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, null, null)
            ) * $defensiveCasualtiesPercentage;
            $drafteesLost = (int)floor($target->military_draftees * $finalCasualtiesPercentage);
        }
        if ($drafteesLost > 0) {
            $target->military_draftees -= $drafteesLost;

            $this->unitsLost += $drafteesLost; // todo: refactor
            $this->invasionResult['defender']['unitsLost']['draftees'] = $drafteesLost;
        }

        // Non-draftees
        foreach ($target->race->units as $unit) {
            if ($unit->power_defense === 0.0) {
                continue;
            }
            $finalCasualtiesPercentage = min(
                $recentInvasionModifier,
                $this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, $unit->slot, $units)
            ) * $defensiveCasualtiesPercentage;
            $slotLost = (int)floor($target->{"military_unit{$unit->slot}"} * $finalCasualtiesPercentage);

            if ($slotLost > 0) {
                $defensiveUnitsLost[$unit->slot] = $slotLost;

                $this->unitsLost += $slotLost; // todo: refactor
            }
        }

        foreach ($defensiveUnitsLost as $slot => $amount) {
            $target->{"military_unit{$slot}"} -= $amount;

            $this->invasionResult['defender']['unitsLost'][$slot] = $amount;
        }

        return $this->unitsLost;
    }

    /**
     * Handles land grabs and losses upon successful invasion.
     *
     * todo: description
     *
     * @param Dominion $dominion
     * @param Dominion $target
     */
    protected function handleLandGrabs(Dominion $dominion, Dominion $target): void
    {
        $this->invasionResult['attacker']['landSize'] = $this->landCalculator->getTotalLand($dominion);
        $this->invasionResult['defender']['landSize'] = $this->landCalculator->getTotalLand($target);

        $isInvasionSuccessful = $this->invasionResult['result']['success'];

        // Nothing to grab if invasion isn't successful :^)
        if (!$isInvasionSuccessful) {
            return;
        }

        if (!isset($this->invasionResult['attacker']['landConquered'])) {
            $this->invasionResult['attacker']['landConquered'] = [];
        }

        if (!isset($this->invasionResult['attacker']['landGenerated'])) {
            $this->invasionResult['attacker']['landGenerated'] = [];
        }

        $range = $this->invasionResult['result']['range'];
        $rangeMultiplier = ($range / 100);

        $landGrabRatio = 1;
        $bonusLandRatio = 2;

        // War Bonus
        if ($this->governmentService->isMutualWarEscalated($dominion->realm, $target->realm)) {
            $landGrabRatio = 1.2;
        } elseif ($this->governmentService->isWarEscalated($dominion->realm, $target->realm) || $this->governmentService->isWarEscalated($target->realm, $dominion->realm)) {
            $landGrabRatio = 1.1;
        }

        $attackerLandWithRatioModifier = ($this->landCalculator->getTotalLand($dominion) * $landGrabRatio);

        if ($range < 55) {
            $acresLost = (0.304 * ($rangeMultiplier ** 2) - 0.227 * $rangeMultiplier + 0.048) * $attackerLandWithRatioModifier;
        } elseif ($range < 75) {
            $acresLost = (0.154 * $rangeMultiplier - 0.069) * $attackerLandWithRatioModifier;
        } else {
            $acresLost = (0.129 * $rangeMultiplier - 0.048) * $attackerLandWithRatioModifier;
        }

        $acresLost *= 0.80;

        $acresLost = (int)max(floor($acresLost), 10);

        $landLossRatio = ($acresLost / $this->landCalculator->getTotalLand($target));
        $landAndBuildingsLostPerLandType = $this->landCalculator->getLandLostByLandType($target, $landLossRatio);

        $landGainedPerLandType = [];
        foreach ($landAndBuildingsLostPerLandType as $landType => $landAndBuildingsLost) {
            if (!isset($this->invasionResult['attacker']['landConquered'][$landType])) {
                $this->invasionResult['attacker']['landConquered'][$landType] = 0;
            }

            if (!isset($this->invasionResult['attacker']['landGenerated'][$landType])) {
                $this->invasionResult['attacker']['landGenerated'][$landType] = 0;
            }

            $buildingsToDestroy = $landAndBuildingsLost['buildingsToDestroy'];
            $landLost = $landAndBuildingsLost['landLost'];
            $buildingsLostForLandType = $this->buildingCalculator->getBuildingTypesToDestroy($target, $buildingsToDestroy, $landType);

            // Remove land
            $target->{"land_$landType"} -= $landLost;

            // Destroy buildings
            foreach ($buildingsLostForLandType as $buildingType => $buildingsLost) {
                $builtBuildingsToDestroy = $buildingsLost['builtBuildingsToDestroy'];
                $resourceName = "building_{$buildingType}";
                $target->$resourceName -= $builtBuildingsToDestroy;

                $buildingsInQueueToRemove = $buildingsLost['buildingsInQueueToRemove'];

                if ($buildingsInQueueToRemove !== 0) {
                    $this->queueService->dequeueResource('construction', $target, $resourceName, $buildingsInQueueToRemove);
                }
            }

            $landConquered = (int)round($landLost);
            $landGenerated = (int)round($landConquered * ($bonusLandRatio - 1));

            // Repeat Invasions generate no land
            if ($this->invasionResult['attacker']['repeatInvasion']) {
                $landGenerated = 0;
            }
            $landGained = ($landConquered + $landGenerated);

            // Racial Spell: Erosion (Lizardfolk, Merfolk), Verdant Bloom (Sylvan)
            if ($this->spellCalculator->isSpellActive($dominion, 'erosion') || $this->spellCalculator->isSpellActive($dominion, 'verdant_bloom')) {
                // todo: needs a more generic solution later
                if ($this->spellCalculator->isSpellActive($dominion, 'verdant_bloom')) {
                    $eventName = 'landVerdantBloom';
                    $landRezoneType = 'forest';
                    $landRezonePercentage = 35;
                } else {
                    $eventName = 'landErosion';
                    $landRezoneType = 'water';
                    $landRezonePercentage = 20;
                }

                $landRezonedConquered = (int)ceil($landConquered * ($landRezonePercentage / 100));
                $landRezonedGenerated = (int)round($landRezonedConquered * ($bonusLandRatio - 1));
                $landGenerated -= $landRezonedGenerated;
                $landGained -= ($landRezonedConquered + $landRezonedGenerated);

                if (!isset($landGainedPerLandType["land_{$landRezoneType}"])) {
                    $landGainedPerLandType["land_{$landRezoneType}"] = 0;
                }
                $landGainedPerLandType["land_{$landRezoneType}"] += ($landRezonedConquered + $landRezonedGenerated);

                if (!isset($this->invasionResult['attacker']['landGenerated'][$landRezoneType])) {
                    $this->invasionResult['attacker']['landGenerated'][$landRezoneType] = 0;
                }
                $this->invasionResult['attacker']['landGenerated'][$landRezoneType] += $landRezonedGenerated;

                if (!isset($this->invasionResult['attacker'][$eventName])) {
                    $this->invasionResult['attacker'][$eventName] = 0;
                }
                $this->invasionResult['attacker'][$eventName] += ($landRezonedConquered + $landRezonedGenerated);
            }

            if (!isset($landGainedPerLandType["land_{$landType}"])) {
                $landGainedPerLandType["land_{$landType}"] = 0;
            }
            $landGainedPerLandType["land_{$landType}"] += $landGained;

            $this->invasionResult['attacker']['landConquered'][$landType] += $landConquered;
            $this->invasionResult['attacker']['landGenerated'][$landType] += $landGenerated;
        }

        $this->landLost = $acresLost;

        $queueData = $landGainedPerLandType;

        // Only gain discounted acres at or above prestige range
        if ($range >= 75) {
            $queueData += [
                'discounted_land' => array_sum($landGainedPerLandType)
            ];
        }

        $this->queueService->queueResources(
            'invasion',
            $dominion,
            $queueData
        );
    }

    /**
     * Handles morale changes for attacker.
     *
     * Attacker morale gets reduced by 5%, more so if they attack a target below
     * 75% range (up to 10% reduction at 40% target range).
     *
     * @param Dominion $dominion
     * @param Dominion $target
     */
    protected function handleMoraleChanges(Dominion $dominion, Dominion $target): void
    {
        $range = $this->invasionResult['result']['range'];

        $dominion->morale -= 5;

        // Increased morale drops for attacking weaker targets
        if ($range < 75) {
            $additionalMoraleChange = max(round((((($range / 100) - 0.4) * 100) / 7) - 5), -5);
            $dominion->morale += $additionalMoraleChange;
        }
    }

    /**
     * @param Dominion $dominion
     * @param array $units
     * @param array $survivingUnits
     * @return array
     */
    protected function handleConversions(
        Dominion $dominion,
        Dominion $target,
        array $units,
        array $survivingUnits
    ): array {
        $spellAscendanceConversionPercentage = 8;
        $spellFeralHungerConversionRate = 25;
        $spellParasiticHungerMultiplier = 20;

        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        $landRatio = min(1, $this->invasionResult['result']['range'] / 100);
        $convertedUnits = array_fill(1, 4, 0);

        if (
            !$isInvasionSuccessful ||
            !in_array($dominion->race->name, ['Dark Elf', 'Lycanthrope', 'Undead'], true) // todo: might want to check for conversion unit perks here, instead of hardcoded race names
        )
        {
            return $convertedUnits;
        }

        $conversionMultiplier = 1;
        $conversionMultiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'parasitic_hunger', $spellParasiticHungerMultiplier);

        $unitsWithConversionPerk = $dominion->race->units->filter(function ($unit) use ($dominion, $units) {
            if (!array_key_exists($unit->slot, $units) || ($units[$unit->slot] === 0)) {
                return false;
            }

            if ($unit->slot == 3 && $this->spellCalculator->isSpellActive($dominion, 'feral_hunger')) {
                return true;
            }

            return $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'conversion');
        });

        $unitConversionRates = $unitsWithConversionPerk->map(function ($unit) use ($dominion, $units, $spellFeralHungerConversionRate) {
            if ($unit->slot == 3 && $this->spellCalculator->isSpellActive($dominion, 'feral_hunger')) {
                $unitSlot = 3;
                $conversionRate = 1 / $spellFeralHungerConversionRate;
            } else {
                $perkValue = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'conversion');
                $unitSlot = (int)$perkValue[0];
                $conversionRate = (1 / (int)$perkValue[1]);
            }

            return [
                'convertSlot' => $unitSlot,
                'unitSlot' => $unit->slot,
                'unitPower' => $unit->power_offense, // TODO: Support units with dynamic power perks
                'conversionRate' => $conversionRate
            ];
        });

        $offensiveModifier = $this->militaryCalculator->getOffensivePowerMultiplier($dominion, $target);
        $targetDP = $this->invasionResult['defender']['dp'];

        foreach ($unitConversionRates->sortByDesc('rate') as $convertingUnit) {
            if ($targetDP <= 0) {
                continue;
            }

            $unitsNeededToBreakTarget = ceil($targetDP / ($convertingUnit['unitPower'] * $offensiveModifier));
            $convertingUnitsForSlot = min($unitsNeededToBreakTarget, $units[$convertingUnit['unitSlot']]);
            $targetDP -= ($convertingUnitsForSlot * $convertingUnit['unitPower'] * $offensiveModifier);
            $converts = floor($convertingUnitsForSlot * $convertingUnit['conversionRate'] * $conversionMultiplier * ($landRatio ** 2));
            $convertedUnits[$convertingUnit['convertSlot']] += $converts;
        }

        // Special case for Ascendance
        if (isset($survivingUnits[1]) && $this->spellCalculator->isSpellActive($dominion, 'ascendance') && $this->invasionResult['result']['range'] >= 75) {
            $ascendedUnits = floor($survivingUnits[1] * $spellAscendanceConversionPercentage / 100);
            $convertedUnits[1] -= $ascendedUnits;
            $convertedUnits[4] += $ascendedUnits;
        }

        if (!isset($this->invasionResult['attacker']['conversion']) && $convertedUnits !== array_fill(1, 4, 0)) {
            $this->invasionResult['attacker']['conversion'] = $convertedUnits;
        }

        return $convertedUnits;
    }

    /**
     * Handles research point generation for attacker.
     *
     * Past day 30 of the round, RP gains by attacking goes up from 1000 and peaks at 1667 on day 50
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleResearchPoints(Dominion $dominion, Dominion $target, array $units): void
    {
        // Repeat Invasions award no research points
        if ($this->invasionResult['attacker']['repeatInvasion']) {
            return;
        }

        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        if ($isInvasionSuccessful) {
            $baseResearchPointsGained = max(1000, $dominion->round->daysInRound() / 0.03);

            // Recent invasion penalty
            $recentlyInvadedCount = $this->militaryCalculator->getRecentlyInvadedCount($dominion, 24 * 3, true);
            $schoolPenalty = (1 - min(0.75, max(0, $recentlyInvadedCount - 2) * 0.15));

            $range = $this->invasionResult['result']['range'];
            if ($range < 60) {
                $researchPointsGained = 0;
            } elseif ($range < 75) {
                $researchPointsGained = $baseResearchPointsGained / 2;
            } else {
                $schoolPercentageCap = 20;
                $schoolPercentage = min(
                    $dominion->building_school / $this->landCalculator->getTotalLand($dominion),
                    $schoolPercentageCap / 100
                );
                $researchPointsGained = (125 * $schoolPercentage * 100 * $schoolPenalty);
                $researchPointsGained = min(5 * $this->landCalculator->getTotalLand($dominion), $researchPointsGained);
                $researchPointsGained = max(0, $researchPointsGained);
                $researchPointsGained += $baseResearchPointsGained;
            }

            $multiplier = 1;

            // Racial Bonus
            $multiplier += $dominion->race->getPerkMultiplier('tech_production');

            // Wonders
            $multiplier += $dominion->getWonderPerkMultiplier('tech_production');

            $researchPointsGained *= $multiplier;

            if($researchPointsGained > 0) {
                $slowestTroopsReturnHours = $this->invasionService->getSlowestUnitReturnHours($dominion, $units);
                $this->queueService->queueResources(
                    'invasion',
                    $dominion,
                    ['resource_tech' => round($researchPointsGained)],
                    $slowestTroopsReturnHours
                );
            }

            $this->invasionResult['attacker']['researchPoints'] = round($researchPointsGained);
        }
    }

    /**
     * Handles perks that trigger on invasion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
    protected function handleAfterInvasionUnitPerks(Dominion $dominion, Dominion $target, array $units): void
    {
        // todo: need a refactor later to take into account more post-combat unit-perk-related stuff

        if (!$this->invasionResult['result']['success']) {
            return; // nothing to plunder on unsuccessful invasions
        }

        $unitsSentPerSlot = [];
        $plunderPlatinum = 0;
        $plunderGems = 0;

        // todo: inefficient to do run this code per slot. needs refactoring
        foreach ($dominion->race->units as $unit) {
            $slot = $unit->slot;

            if (!isset($units[$slot])) {
                continue;
            }

            // We have a unit with plunder!
            if ($unit->getPerkValue('plunder_platinum') != 0) {
                $plunderPlatinum += $units[$slot] * (int)$unit->getPerkValue('plunder_platinum');
            }
            if ($unit->getPerkValue('plunder_gems') != 0) {
                $plunderGems += $units[$slot] * (int)$unit->getPerkValue('plunder_gems');
            }
        }

        // We have a unit with plunder!
        if ($plunderPlatinum > 0 || $plunderGems > 0) {
            $productionCalculator = app(\OpenDominion\Calculators\Dominion\ProductionCalculator::class);

            $plunderPlatinum = min($plunderPlatinum, (int)floor($productionCalculator->getPlatinumProductionRaw($target)));
            $plunderGems = min($plunderGems, (int)floor($productionCalculator->getGemProductionRaw($target)));

            if (!isset($this->invasionResult['attacker']['plunder'])) {
                $this->invasionResult['attacker']['plunder'] = [
                    'platinum' => $plunderPlatinum,
                    'gems' => $plunderGems,
                ];
            }

            $slowestTroopsReturnHours = $this->invasionService->getSlowestUnitReturnHours($dominion, $units);
            $this->queueService->queueResources(
                'invasion',
                $dominion,
                [
                    'resource_platinum' => $plunderPlatinum,
                    'resource_gems' => $plunderGems,
                ],
                $slowestTroopsReturnHours
            );
        }

        // Plague from Parasitic Hunger
        if ($this->spellCalculator->isSpellActive($dominion, 'parasitic_hunger')) {
            $this->invasionService->applySpell($dominion, $target, 'plague', 8);
        }
        if ($this->spellCalculator->isSpellActive($target, 'parasitic_hunger')) {
            $this->invasionService->applySpell($target, $dominion, 'plague', 8);
        }
    }

    /**
     * Handles the surviving units returning home.
     *
     * @param Dominion $dominion
     * @param array $units
     * @param array $convertedUnits
     */
    protected function handleReturningUnits(Dominion $dominion, array $units, array $convertedUnits): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $unitKey = "military_unit{$i}";
            $returningAmount = 0;

            if (array_key_exists($i, $units)) {
                $returningAmount += $units[$i];
                $dominion->$unitKey -= $units[$i];
            }

            if (array_key_exists($i, $convertedUnits)) {
                $returningAmount += $convertedUnits[$i];
            }

            if ($returningAmount === 0) {
                continue;
            }

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                [$unitKey => $returningAmount],
                $this->invasionService->getUnitReturnHoursForSlot($dominion, $i)
            );
        }
    }

    /**
     * Handles the returning boats.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
    protected function handleBoats(Dominion $dominion, Dominion $target, array $units): void
    {
        $unitsTotal = 0;
        $unitsThatSinkBoats = 0;
        $unitsThatNeedsBoatsByReturnHours = [];
        // Calculate boats sent and attacker sinking perk
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            $unitsTotal += (int)$units[$unit->slot];

            if ($unit->getPerkValue('sink_boats_offense') != 0) {
                $unitsThatSinkBoats += (int)$units[$unit->slot];
            }

            if ($unit->need_boat) {
                $hours = $this->invasionService->getUnitReturnHoursForSlot($dominion, $unit->slot);

                if (!isset($unitsThatNeedsBoatsByReturnHours[$hours])) {
                    $unitsThatNeedsBoatsByReturnHours[$hours] = 0;
                }

                $unitsThatNeedsBoatsByReturnHours[$hours] += (int)$units[$unit->slot];
            }
        }
        if (!$this->invasionResult['result']['overwhelmed'] && $unitsThatSinkBoats > 0) {
            $defenderBoatsProtected = $this->militaryCalculator->getBoatsProtected($target);
            $defenderBoatsSunkPercentage = (static::BOATS_SUNK_BASE_PERCENTAGE / 100) * ($unitsThatSinkBoats / $unitsTotal);
            $targetQueuedBoats = $this->queueService->getInvasionQueueTotalByResource($target, 'resource_boats');
            $targetBoatTotal = $target->resource_boats + $targetQueuedBoats;
            $defenderBoatsSunk = (int)floor(max(0, $targetBoatTotal - $defenderBoatsProtected) * $defenderBoatsSunkPercentage);
            if ($defenderBoatsSunk > $targetQueuedBoats) {
                $this->queueService->dequeueResource('invasion', $target, 'boats', $targetQueuedBoats);
                $target->resource_boats -= $defenderBoatsSunk - $targetQueuedBoats;
            } else {
                $this->queueService->dequeueResource('invasion', $target, 'boats', $defenderBoatsSunk);
            }
            $this->invasionResult['defender']['boatsLost'] = $defenderBoatsSunk;
        }

        $defendingUnitsTotal = 0;
        $defendingUnitsThatSinkBoats = 0;
        $attackerBoatsLost = 0;
        // Defender sinking perk
        foreach ($target->race->units as $unit) {
            $defendingUnitsTotal += $target->{"military_unit{$unit->slot}"};
            if ($unit->getPerkValue('sink_boats_defense') != 0) {
                $defendingUnitsThatSinkBoats += $target->{"military_unit{$unit->slot}"};
            }
        }
        if ($defendingUnitsThatSinkBoats > 0) {
            $attackerBoatsSunkPercentage = (static::BOATS_SUNK_BASE_PERCENTAGE / 100) * ($defendingUnitsThatSinkBoats / $defendingUnitsTotal);
        }

        // Queue returning boats
        foreach ($unitsThatNeedsBoatsByReturnHours as $hours => $amountUnits) {
            $boatsByReturnHourGroup = (int)floor($amountUnits / $this->militaryCalculator->getBoatCapacity($dominion));

            $dominion->resource_boats -= $boatsByReturnHourGroup;

            if ($defendingUnitsThatSinkBoats > 0) {
                $attackerBoatsSunk = (int)ceil($boatsByReturnHourGroup * $attackerBoatsSunkPercentage);
                $attackerBoatsLost += $attackerBoatsSunk;
                $boatsByReturnHourGroup -= $attackerBoatsSunk;
            }

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['resource_boats' => $boatsByReturnHourGroup],
                $hours
            );
        }
        if ($attackerBoatsLost > 0) {
            $this->invasionResult['attacker']['boatsLost'] = $attackerBoatsSunk;
        }
    }

    /**
     * Check whether the invasion is successful.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return bool
     */
    protected function checkInvasionSuccess(Dominion $dominion, Dominion $target, array $units): void
    {
        $landRatio = $this->invasionResult['result']['range'] / 100;
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);
        $targetDP = $this->getDefensivePowerWithTemples($dominion, $target);
        $this->invasionResult['attacker']['op'] = $attackingForceOP;
        $this->invasionResult['defender']['dp'] = $targetDP;
        $this->invasionResult['result']['success'] = ($attackingForceOP > $targetDP);
    }

    /**
     * Check whether the attackers got overwhelmed by the target's defending army.
     *
     * Overwhelmed attackers have increased casualties, while the defending
     * party has reduced casualties.
     *
     */
    protected function checkOverwhelmed(): void
    {
        // Never overwhelm on successful invasions
        $this->invasionResult['result']['overwhelmed'] = false;

        if ($this->invasionResult['result']['success']) {
            return;
        }

        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];

        $this->invasionResult['result']['overwhelmed'] = ((1 - $attackingForceOP / $targetDP) >= (static::OVERWHELMED_PERCENTAGE / 100));
    }

    protected function getDefensivePowerWithTemples(Dominion $dominion, Dominion $target): float
    {
        $dpMultiplierReduction = $this->militaryCalculator->getTempleReduction($dominion);

        $ignoreDraftees = false;
        if ($this->spellCalculator->isSpellActive($dominion, 'unholy_ghost')) {
            $ignoreDraftees = true;
        }

        return $this->militaryCalculator->getDefensivePower($target, $dominion, null, null, $dpMultiplierReduction, $ignoreDraftees);
    }
}
