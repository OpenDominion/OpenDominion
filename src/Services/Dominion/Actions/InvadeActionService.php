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
    protected const CASUALTIES_DEFENSIVE_BASE_PERCENTAGE = 3.825;

    /**
     * @var float Max percentage of defensive casualties
     */
    protected const CASUALTIES_DEFENSIVE_MAX_PERCENTAGE = 6.0;

    /**
     * @var float Base percentage of offensive casualties
     */
    protected const CASUALTIES_OFFENSIVE_BASE_PERCENTAGE = 8.5;

    /**
     * @var int The minimum morale required to initiate an invasion
     */
    protected const MIN_MORALE = 70;

    /**
     * @var float Failing an invasion by this percentage (or more) results in 'being overwhelmed'
     */
    protected const OVERWHELMED_PERCENTAGE = 15.0;

    /**
     * @var int Bonus prestige when invading successfully
     */
    protected const PRESTIGE_CHANGE_ADD = 30;

    /**
     * @var float Base prestige % change for both parties when invading
     */
    protected const PRESTIGE_CHANGE_PERCENTAGE = 5.0;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var GovernmentService */
    protected $governmentService;

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
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        NotificationService $notificationService,
        ProtectionService $protectionService,
        QueueService $queueService,
        RangeCalculator $rangeCalculator,
        SpellCalculator $spellCalculator
    ) {
        $this->buildingCalculator = $buildingCalculator;
        $this->casualtiesCalculator = $casualtiesCalculator;
        $this->governmentService = $governmentService;
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
    public function invade(Dominion $dominion, Dominion $target, array $units): array
    {
        DB::transaction(function () use ($dominion, $target, $units) {
            // Checks
            $this->guardLockedDominion($dominion);

            if ($dominion->round->hasOffensiveActionsDisabled()) {
                throw new GameException('Invasions have been disabled for the remainder of the round.');
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
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;

            if (!$this->hasAnyOP($dominion, $units)) {
                throw new GameException('You need to send at least some units');
            }

            if (!$this->allUnitsHaveOP($dominion, $units)) {
                throw new GameException('You cannot send units that have no OP');
            }

            if (!$this->hasEnoughUnitsAtHome($dominion, $units)) {
                throw new GameException('You don\'t have enough units at home to send this many units');
            }

            if (!$this->hasEnoughBoats($dominion, $units)) {
                throw new GameException('You do not have enough boats to send this many units');
            }

            if ($dominion->morale < static::MIN_MORALE) {
                throw new GameException('You do not have enough morale to invade others');
            }

            if (!$this->passes33PercentRule($dominion, $target, $units)) {
                throw new GameException('You need to leave more DP units at home, based on the OP you\'re sending out (33% rule)');
            }

            if (!$this->passes54RatioRule($dominion, $target, $landRatio, $units)) {
                throw new GameException('You are sending out too much OP, based on your new home DP (5:4 rule)');
            }

            foreach($units as $amount) {
                if($amount < 0) {
                    throw new GameException('Invasion was canceled due to bad input.');
                }
            }

            // Handle invasion results
            $this->checkInvasionSuccess($dominion, $target, $units);
            $this->checkOverwhelmed();

            if(!$this->passesOpAtLeast50percentOfDpRule()) {
                throw new GameException('You are not sending enough OP to be even close to breaking the target (50% rule)');
            }

            $this->rangeCalculator->checkGuardApplications($dominion, $target);

            $this->handleBoats($dominion, $target, $units);
            $this->handlePrestigeChanges($dominion, $target, $units);

            $survivingUnits = $this->handleOffensiveCasualties($dominion, $target, $units);
            $totalDefensiveCasualties = $this->handleDefensiveCasualties($dominion, $target);
            $convertedUnits = $this->handleConversions($dominion, $landRatio, $units, $totalDefensiveCasualties);

            $this->handleReturningUnits($dominion, $survivingUnits, $convertedUnits);
            $this->handleAfterInvasionUnitPerks($dominion, $target, $survivingUnits);

            $this->handleMoraleChanges($dominion, $target);
            $this->handleLandGrabs($dominion, $target);
            $this->handleResearchPoints($dominion, $survivingUnits);

            $this->invasionResult['attacker']['unitsSent'] = $units;

            // Stat changes
            // todo: move to own method
            if ($this->invasionResult['result']['success']) {
                $dominion->stat_total_land_conquered += (int)array_sum($this->invasionResult['attacker']['landConquered']);
                $dominion->stat_total_land_explored += (int)array_sum($this->invasionResult['attacker']['landGenerated']);
                $dominion->stat_attacking_success += 1;
            } else {
                $target->stat_defending_success += 1;
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

            $target->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
            $dominion->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
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
        $range = $this->rangeCalculator->getDominionRange($dominion, $target);

        $attackerPrestigeChange = 0;
        $targetPrestigeChange = 0;

        if ($isOverwhelmed || ($range < 60)) {
            $attackerPrestigeChange = ($dominion->prestige * -(static::PRESTIGE_CHANGE_PERCENTAGE / 100));
        } elseif ($isInvasionSuccessful && ($range >= 75)) {
            $attackerPrestigeChange = (int)round(($target->prestige * (($range / 100) / 10)) + static::PRESTIGE_CHANGE_ADD);
            $targetPrestigeChange = (int)round($target->prestige * -(static::PRESTIGE_CHANGE_PERCENTAGE / 100));

            // War Bonus
            if ($this->governmentService->isAtMutualWarWithRealm($dominion->realm, $target->realm)) {
                $attackerPrestigeChange *= 1.25;
            } elseif ($this->governmentService->isAtWarWithRealm($dominion->realm, $target->realm)) {
                $attackerPrestigeChange *= 1.15;
            }
        }

        // Reduce attacker prestige gain if the target was hit recently
        if($attackerPrestigeChange > 0) {
            $recentlyInvadedCount = $this->militaryCalculator->getRecentlyInvadedCount($target);

            if ($recentlyInvadedCount === 1) {
                $attackerPrestigeChange *= 0.75;
            } elseif ($recentlyInvadedCount === 2) {
                $attackerPrestigeChange *= 0.5;
            } elseif ($recentlyInvadedCount === 3) {
                $attackerPrestigeChange *= 0.25;
            } elseif ($recentlyInvadedCount === 4) {
                $attackerPrestigeChange *= -0.25;
            } elseif ($recentlyInvadedCount >= 5) {
                $attackerPrestigeChange *= -0.5;
            }

            $this->invasionResult['defender']['recentlyInvadedCount'] = $recentlyInvadedCount;
        }

        // Cap for max prestige based on land size
        $maxPrestigeChange = max(($this->landCalculator->getTotalLand($dominion) * 1.2) - $dominion->prestige, 0);
        $attackerPrestigeChange = min($attackerPrestigeChange, $maxPrestigeChange);

        if ($attackerPrestigeChange !== 0) {
            if (!$isInvasionSuccessful) {
                // Unsuccessful invasions (bounces) give negative prestige immediately
                $dominion->prestige += $attackerPrestigeChange;

            } else {
                // todo: possible bug if all 12hr units die (somehow) and only 9hr units survive, prestige gets returned after 12 hrs, since $units is input, not surviving units. fix?
                $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($dominion, $units);

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
        $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];
        $offensiveCasualtiesPercentage = (static::CASUALTIES_OFFENSIVE_BASE_PERCENTAGE / 100);

        $offensiveUnitsLost = [];

        if ($isInvasionSuccessful) {
            $totalUnitsSent = array_sum($units);

            $averageOPPerUnitSent = ($attackingForceOP / $totalUnitsSent);
            $OPNeededToBreakTarget = ($targetDP + 1);
            $unitsNeededToBreakTarget = round($OPNeededToBreakTarget / $averageOPPerUnitSent);

            $totalUnitsLeftToKill = ceil($unitsNeededToBreakTarget * $offensiveCasualtiesPercentage);

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
            if ($isOverwhelmed) {
                $offensiveCasualtiesPercentage *= 2;
            }

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
    protected function handleDefensiveCasualties(Dominion $dominion, Dominion $target): int
    {
        if ($this->invasionResult['result']['overwhelmed'])
        {
            return 0;
        }

        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];
        $defensiveCasualtiesPercentage = (static::CASUALTIES_DEFENSIVE_BASE_PERCENTAGE / 100);

        // Modify casualties percentage based on relative land size
        $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
        $defensiveCasualtiesPercentage *= clamp($landRatio, 0.4, 1);

        // Scale casualties further with invading OP vs target DP
        $defensiveCasualtiesPercentage *= ($attackingForceOP / $targetDP);

        // Reduce casualties if target has been hit recently
        $recentlyInvadedCount = $this->militaryCalculator->getRecentlyInvadedCount($target);

        if ($recentlyInvadedCount === 1) {
            $defensiveCasualtiesPercentage *= 0.8;
        } elseif ($recentlyInvadedCount === 2) {
            $defensiveCasualtiesPercentage *= 0.6;
        } elseif ($recentlyInvadedCount === 3) {
            $defensiveCasualtiesPercentage *= 0.55;
        } elseif ($recentlyInvadedCount === 4) {
            $defensiveCasualtiesPercentage *= 0.45;
        } elseif ($recentlyInvadedCount >= 5) {
            $defensiveCasualtiesPercentage *= 0.35;
        }

        // Cap max casualties
        $defensiveCasualtiesPercentage = min(
            $defensiveCasualtiesPercentage,
            (static::CASUALTIES_DEFENSIVE_MAX_PERCENTAGE / 100)
        );

        $defensiveUnitsLost = [];

        // Draftees
        if ($this->spellCalculator->isSpellActive($dominion, 'unholy_ghost')) {
            $drafteesLost = 0;
        } else {
            $drafteesLost = (int)floor($target->military_draftees * $defensiveCasualtiesPercentage *
                $this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, null));
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

            $slotLost = (int)floor($target->{"military_unit{$unit->slot}"} * $defensiveCasualtiesPercentage *
                $this->casualtiesCalculator->getDefensiveCasualtiesMultiplierForUnitSlot($target, $dominion, $unit->slot));

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

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        $rangeMultiplier = ($range / 100);

        $landGrabRatio = 1;
        $bonusLandRatio = 1.7647;

        // War Bonus
        if ($this->governmentService->isAtMutualWarWithRealm($dominion->realm, $target->realm)) {
            $landGrabRatio = 1.2;
        } elseif ($this->governmentService->isAtWarWithRealm($dominion->realm, $target->realm)) {
            $landGrabRatio = 1.15;
        }

        $attackerLandWithRatioModifier = ($this->landCalculator->getTotalLand($dominion) * $landGrabRatio);

        if ($range < 55) {
            $acresLost = (0.304 * ($rangeMultiplier ** 2) - 0.227 * $rangeMultiplier + 0.048) * $attackerLandWithRatioModifier;
        } elseif ($range < 75) {
            $acresLost = (0.154 * $rangeMultiplier - 0.069) * $attackerLandWithRatioModifier;
        } else {
            $acresLost = (0.129 * $rangeMultiplier - 0.048) * $attackerLandWithRatioModifier;
        }

        $acresLost *= 0.85;

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

            // Add discounted land for buildings destroyed
            $target->discounted_land += $buildingsToDestroy;

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
            $landGained = ($landConquered + $landGenerated);

            // Racial Spell: Erosion (Lizardfolk, Merfolk)
            if ($this->spellCalculator->isSpellActive($dominion, 'erosion')) {
                // todo: needs a more generic solution later
                $landRezoneType = 'water';
                $landRezonePercentage = 20;

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

                if (!isset($this->invasionResult['attacker']['landErosion'])) {
                    $this->invasionResult['attacker']['landErosion'] = 0;
                }
                $this->invasionResult['attacker']['landErosion'] += ($landRezonedConquered + $landRezonedGenerated);
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
        $range = $this->rangeCalculator->getDominionRange($dominion, $target);

        $dominion->morale -= 5;

        // Increased morale drops for attacking weaker targets
        if ($range < 75) {
            $additionalMoraleChange = max(round((((($range / 100) - 0.4) * 100) / 7) - 5), -5);
            $dominion->morale += $additionalMoraleChange;
        }
    }

    /**
     * @param Dominion $dominion
     * @param float $landRatio
     * @param array $units
     * @param int $totalDefensiveCasualties
     * @return array
     */
    protected function handleConversions(
        Dominion $dominion,
        float $landRatio,
        array $units,
        int $totalDefensiveCasualties
    ): array {
        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        $convertedUnits = array_fill(1, 4, 0);

        if (
            !$isInvasionSuccessful ||
            ($totalDefensiveCasualties === 0) ||
            !in_array($dominion->race->name, ['Lycanthrope', 'Spirit', 'Undead'], true) // todo: might want to check for conversion unit perks here, instead of hardcoded race names
        ) {
            return $convertedUnits;
        }

        $conversionBaseMultiplier = 0.06;
        $spellParasiticHungerMultiplier = 50;

        $conversionMultiplier = (
            $conversionBaseMultiplier *
            (1 + $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'parasitic_hunger', $spellParasiticHungerMultiplier))
        );

        $totalConvertingUnits = 0;

        $unitsWithConversionPerk = $dominion->race->units->filter(static function (Unit $unit) use (
            $landRatio,
            $units,
            $dominion
        ) {
            if (!array_key_exists($unit->slot, $units) || ($units[$unit->slot] === 0)) {
                return false;
            }

            $staggeredConversionPerk = $dominion->race->getUnitPerkValueForUnitSlot(
                $unit->slot,
                'staggered_conversion');

            if ($staggeredConversionPerk) {
                foreach ($staggeredConversionPerk as $rangeConversionPerk) {
                    $range = ((int)$rangeConversionPerk[0]) / 100;
                    if ($range <= $landRatio) {
                        return true;
                    }
                }

                return false;
            }

            return $unit->getPerkValue('conversion');
        });

        foreach ($unitsWithConversionPerk as $unit) {
            $totalConvertingUnits += $units[$unit->slot];
        }

        $totalConverts = min($totalConvertingUnits * $conversionMultiplier, $totalDefensiveCasualties * 1.75) * $landRatio;

        foreach ($unitsWithConversionPerk as $unit) {
            $conversionPerk = $unit->getPerkValue('conversion');
            $convertingUnitsForSlot = $units[$unit->slot];
            $convertingUnitsRatio = $convertingUnitsForSlot / $totalConvertingUnits;
            $totalConversionsForUnit = floor($totalConverts * $convertingUnitsRatio);

            if (!$conversionPerk) {
                $staggeredConversionPerk = $dominion->race->getUnitPerkValueForUnitSlot(
                    $unit->slot,
                    'staggered_conversion'
                );

                foreach ($staggeredConversionPerk as $rangeConversionPerk) {
                    $range = ((int)$rangeConversionPerk[0]) / 100;
                    $slots = $rangeConversionPerk[1];

                    if ($range > $landRatio) {
                        continue;
                    }

                    $conversionPerk = $slots;
                }
            }

            $slotsToConvertTo = strlen($conversionPerk);
            $totalConvertsForSlot = floor($totalConversionsForUnit / $slotsToConvertTo);

            foreach (str_split($conversionPerk) as $slot) {
                $convertedUnits[(int)$slot] += (int)$totalConvertsForSlot;
            }
        }

        if (!isset($this->invasionResult['attacker']['conversion']) && array_sum($convertedUnits) > 0) {
            $this->invasionResult['attacker']['conversion'] = $convertedUnits;
        }

        return $convertedUnits;
    }

    /**
     * Handles research point generation for attacker.
     *
     * Original formula:
     * (Conquered acres) * max([(Days into the round)/3], 10)
     * - Past day 30 of the round, RP gains by attacking goes up and peaks at 16.6667 on day 50
     * - This number is increased by 50% in the Base Ruleset.
     *
     * Removing days into round portion of the formula in favor of a static coefficient.
     * For base ruleset, the range for the coefficent was 15-25. We are using 20 instead.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleResearchPoints(Dominion $dominion, array $units): void
    {
        $researchPointsPerAcre = 20;

        $isInvasionSuccessful = $this->invasionResult['result']['success'];
        if ($isInvasionSuccessful) {
            $landConquered = array_sum($this->invasionResult['attacker']['landConquered']);
            $landGenerated = array_sum($this->invasionResult['attacker']['landGenerated']);

            $researchPointsGained = ($landConquered + $landGenerated) * $researchPointsPerAcre;
            $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($dominion, $units);

            // Racial Bonus
            $researchPointsGained *= (1 + $dominion->race->getPerkMultiplier('tech_production'));

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['resource_tech' => $researchPointsGained],
                $slowestTroopsReturnHours
            );

            $this->invasionResult['attacker']['researchPoints'] = $researchPointsGained;
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
        // todo: just hobgoblin plunder atm, need a refactor later to take into
        //       account more post-combat unit-perk-related stuff

        $isInvasionSuccessful = $this->invasionResult['result']['success'];

        if (!$isInvasionSuccessful) {
            return; // nothing to plunder on unsuccessful invasions
        }

        $attackingForceOP = $this->invasionResult['attacker']['op'];
        $targetDP = $this->invasionResult['defender']['dp'];

        // todo: refactor this hardcoded hacky mess
        // Check if we sent hobbos out
        if (($dominion->race->name === 'Goblin') && isset($units[3]) && ($units[3] > 0)) {
            $hobbos = $units[3];
            $totalUnitsSent = array_sum($units);

            $hobbosPercentage = $hobbos / $totalUnitsSent;

            $averageOPPerUnitSent = ($attackingForceOP / $totalUnitsSent);
            $OPNeededToBreakTarget = ($targetDP + 1);
            $unitsNeededToBreakTarget = round($OPNeededToBreakTarget / $averageOPPerUnitSent);

            $hobbosToPlunderWith = (int)ceil($unitsNeededToBreakTarget * $hobbosPercentage);

            $plunderPlatinum = min($hobbosToPlunderWith * 50, (int)floor($target->resource_platinum * 0.2));
            $plunderGems = min($hobbosToPlunderWith * 20, (int)floor($target->resource_gems * 0.2));

            $target->resource_platinum -= $plunderPlatinum;
            $target->resource_gems -= $plunderGems;

            if (!isset($this->invasionResult['attacker']['plunder'])) {
                $this->invasionResult['attacker']['plunder'] = [
                    'platinum' => $plunderPlatinum,
                    'gems' => $plunderGems,
                ];
            }

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                [
                    'resource_platinum' => $plunderPlatinum,
                    'resource_gems' => $plunderGems,
                ]
            );
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
                $this->getUnitReturnHoursForSlot($dominion, $i)
            );
        }
    }

    /**
     * Handles the surviving units returning home.
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

            if ($unit->getPerkValue('sink_boats_offense') !== 0) {
                $unitsThatSinkBoats += (int)$units[$unit->slot];
            }

            if ($unit->need_boat) {
                $hours = $this->getUnitReturnHoursForSlot($dominion, $unit->slot);

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
            if ($unit->getPerkValue('sink_boats_defense') !== 0) {
                $defendingUnitsThatSinkBoats += $target->{"military_unit{$unit->slot}"};
            }
        }
        if ($defendingUnitsThatSinkBoats > 0) {
            $attackerBoatsSunkPercentage = (static::BOATS_SUNK_BASE_PERCENTAGE / 100) * ($defendingUnitsThatSinkBoats / $defendingUnitsTotal);
        }

        // Queue returning boats
        foreach ($unitsThatNeedsBoatsByReturnHours as $hours => $amountUnits) {
            $boatsByReturnHourGroup = (int)floor($amountUnits / $dominion->race->getBoatCapacity());

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
        $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
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

    protected function passesOpAtLeast50percentOfDpRule(): bool
    {
        if($this->invasionResult['result']['success']) {
            return true;
        }

        return $this->invasionResult['attacker']['op'] / $this->invasionResult['defender']['dp'] > 0.5;
    }

    /**
     * Check if dominion is sending out at least *some* OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasAnyOP(Dominion $dominion, array $units): bool
    {
        return ($this->militaryCalculator->getOffensivePower($dominion, null, null, $units) !== 0.0);
    }

    /**
     * Check if all units being sent have positive OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function allUnitsHaveOP(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->power_offense === 0.0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($units[$unit->slot] > $dominion->{'military_unit' . $unit->slot}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough boats to send units out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasEnoughBoats(Dominion $dominion, array $units): bool
    {
        $unitsThatNeedBoats = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->need_boat) {
                $unitsThatNeedBoats += (int)$units[$unit->slot];
            }
        }

        return ($dominion->resource_boats >= ceil($unitsThatNeedBoats / $dominion->race->getBoatCapacity()));
    }

    /**
     * Check if an invasion passes the 33%-rule.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return bool
     */
    protected function passes33PercentRule(Dominion $dominion, Dominion $target, array $units): bool
    {
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, null, $units);
        $attackingForceDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $units);
        $currentHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion);

        $unitsReturning = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $unitsReturning[$slot] = $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
        }

        $returningForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsReturning);

        $totalDP = $currentHomeForcesDP + $returningForcesDP;

        $newHomeForcesDP = ($currentHomeForcesDP - $attackingForceDP);

        return ($newHomeForcesDP >= $totalDP * (1/3));
    }

    /**
     * Check if an invasion passes the 5:4-rule.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function passes54RatioRule(Dominion $dominion, Dominion $target, float $landRatio, array $units): bool
    {
        $unitsHome = [
            0 => $dominion->military_draftees,
            1 => $dominion->military_unit1 - (isset($units[1]) ? $units[1] : 0),
            2 => $dominion->military_unit2 - (isset($units[2]) ? $units[2] : 0),
            3 => $dominion->military_unit3 - (isset($units[3]) ? $units[3] : 0),
            4 => $dominion->military_unit4 - (isset($units[4]) ? $units[4] : 0)
        ];
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome);

        $attackingForceMaxOP = (int)ceil($newHomeForcesDP * 1.25);

        return ($attackingForceOP <= $attackingForceMaxOP);
    }

    /**
     * Returns the amount of hours a military unit (with a specific slot) takes
     * to return home after battle.
     *
     * @param Dominion $dominion
     * @param int $slot
     * @return int
     */
    protected function getUnitReturnHoursForSlot(Dominion $dominion, int $slot): int
    {
        $hours = 12;

        /** @var Unit $unit */
        $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        if ($unit->getPerkValue('faster_return') !== 0) {
            $hours -= (int)$unit->getPerkValue('faster_return');
        }

        return $hours;
    }

    /**
     * Gets the amount of hours for the slowest unit from an array of units
     * takes to return home.
     *
     * Primarily used to bring prestige home earlier if you send only 9hr
     * attackers. (Land always takes 12 hrs)
     *
     * @param Dominion $dominion
     * @param array $units
     * @return int
     */
    protected function getSlowestUnitReturnHours(Dominion $dominion, array $units): int
    {
        $hours = 0;

        foreach ($units as $slot => $amount) {
            if ($amount === 0) {
                continue;
            }

            $hoursForUnit = $this->getUnitReturnHoursForSlot($dominion, $slot);

            if ($hoursForUnit > $hours) {
                $hours = $hoursForUnit;
            }
        }

        if ($hours == 0) {
            $hours = 12;
        }

        return $hours;
    }

    protected function getDefensivePowerWithTemples(Dominion $dominion, Dominion $target): float
    {
        // Values (percentages)
        $dpReductionPerTemple = 1.5;
        $templeMaxDpReduction = 25;
        $ignoreDraftees = false;

        $dpMultiplierReduction = min(
            (($dpReductionPerTemple * $dominion->building_temple) / $this->landCalculator->getTotalLand($dominion)),
            ($templeMaxDpReduction / 100)
        );

        if ($this->spellCalculator->isSpellActive($dominion, 'unholy_ghost')) {
            $ignoreDraftees = true;
        }

        return $this->militaryCalculator->getDefensivePower($target, $dominion, null, null, $dpMultiplierReduction, $ignoreDraftees);
    }
}
