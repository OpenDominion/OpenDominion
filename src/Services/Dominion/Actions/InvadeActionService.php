<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class InvadeActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Base percentage of defensive casualties
     */
    protected const CASUALTIES_DEFENSIVE_BASE_PERCENTAGE = 4.5;

    /**
     * @var float Defensive casualties percentage addition for every 1% land difference
     */
    protected const CASUALTIES_DEFENSIVE_LAND_DIFFERENCE_ADD = 0.035;

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
     * @var float Percentage of attacker prestige used to cap prestige gains (plus bonus)
     */
    protected const PRESTIGE_CAP_PERCENTAGE = 10.0;

    /**
     * @var int Bonus prestige when invading successfully
     */
    protected const PRESTIGE_CHANGE_ADD = 20;

    /**
     * @var float Base prestige % change for both parties when invading
     */
    protected const PRESTIGE_CHANGE_PERCENTAGE = 5.0;

    /**
     * @var int How many units can fit in a single boat
     */
    protected const UNITS_PER_BOAT = 30;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var QueueService */
    protected $queueService;

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
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param NotificationService $notificationService
     * @param ProtectionService $protectionService
     * @param RangeCalculator $rangeCalculator
     * @param QueueService $queueService
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        CasualtiesCalculator $casualtiesCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        NotificationService $notificationService,
        ProtectionService $protectionService,
        RangeCalculator $rangeCalculator,
        QueueService $queueService)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->casualtiesCalculator = $casualtiesCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->notificationService = $notificationService;
        $this->protectionService = $protectionService;
        $this->rangeCalculator = $rangeCalculator;
        $this->queueService = $queueService;
    }

    /**
     * Invades dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws Throwable
     */
    public function invade(Dominion $dominion, Dominion $target, array $units): array
    {
        DB::transaction(function () use ($dominion, $target, $units) {
            // Checks
            $this->guardLockedDominion($dominion);

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new RuntimeException('You cannot invade while under protection');
            }

            if ($this->protectionService->isUnderProtection($target)) {
                throw new RuntimeException('You cannot invade dominions which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target)) {
                throw new RuntimeException('You cannot invade dominions outside of your range');
            }

            if ($dominion->round->id !== $target->round->id) {
                throw new RuntimeException('Nice try, but you cannot invade cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new RuntimeException('Nice try, but you cannot invade your realmies');
            }

            // Sanitize input
            $units = array_map('intval', array_filter($units));

            if (!$this->hasAnyOP($dominion, $units)) {
                throw new RuntimeException('You need to send at least some units');
            }

            if (!$this->allUnitsHaveOP($dominion, $units)) {
                throw new RuntimeException('You cannot send units that have no OP');
            }

            if (!$this->hasEnoughUnitsAtHome($dominion, $units)) {
                throw new RuntimeException('You don\'t have enough units at home to send this many units');
            }

            if (!$this->hasEnoughBoats($dominion, $units)) {
                throw new RuntimeException('You do not have enough boats to send this many units');
            }

            if ($dominion->morale < static::MIN_MORALE) {
                throw new RuntimeException('You do not have enough morale to invade others');
            }

            if (!$this->passes33PercentRule($dominion, $units)) {
                throw new RuntimeException('You need to leave more DP units at home, based on the OP you\'re sending out (33% rule)');
            }

            if (!$this->passes54RatioRule($dominion, $units)) {
                throw new RuntimeException('You are sending out too much OP, based on your new home DP (5:4 rule)');
            }

            // Handle invasion results
            $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);
            $isOverwhelmed = $this->isOverwhelmed($dominion, $target, $units);

            $this->handlePrestigeChanges($dominion, $target, $units);
            $this->handleLandGrabs($dominion, $target, $units);
            $this->handleMoraleChanges($dominion, $target, $units);
            $this->handleConversions($dominion, $target, $units);
            $this->handleUnitPerks($dominion, $target, $units);

            $survivingUnits = $this->handleOffensiveCasualties($dominion, $target, $units);
            $this->handleDefensiveCasualties($dominion, $target, $units);

            $this->handleReturningUnits($dominion, $survivingUnits);

            // todo: refactor
            $this->invasionResult['result']['success'] = $isInvasionSuccessful;

            if ($isOverwhelmed) {
                $this->invasionResult['result']['overwhelmed'] = $isOverwhelmed;
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
            if ($isInvasionSuccessful) {
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
                    'attackerWasOverwhelmed' => $isOverwhelmed,
                    'unitsLost' => $this->unitsLost,
                ]);
            }

            // todo: post to both TCs?

//            dd('foo ');

//            $target->save();
//            $dominion->save();
        });

//        $this->notificationService->sendNotifications($dominion, 'irregular_dominion'); // todo: remove me
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
//            'data' => [
//                //
//            ],
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
     * @throws Throwable
     */
    protected function handlePrestigeChanges(Dominion $dominion, Dominion $target, array $units): void
    {
        $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);
        $isOverwhelmed = $this->isOverwhelmed($dominion, $target, $units);
        $range = $this->rangeCalculator->getDominionRange($dominion, $target);

        $attackerPrestigeChange = 0;
        $targetPrestigeChange = 0;

        if ($isOverwhelmed || ($range < 66)) {
            $attackerPrestigeChange = ($dominion->prestige * -(static::PRESTIGE_CHANGE_PERCENTAGE / 100));

        } elseif ($isInvasionSuccessful && ($range >= 75) && ($range < 120)) {
            $attackerPrestigeChange = (int)round(min(
                (($target->prestige * (static::PRESTIGE_CHANGE_PERCENTAGE / 100)) + static::PRESTIGE_CHANGE_ADD), // Gained through invading
                (($dominion->prestige * (static::PRESTIGE_CAP_PERCENTAGE / 100)) + static::PRESTIGE_CHANGE_ADD) // But capped by depending on your current prestige
            ));
            $targetPrestigeChange = (int)round(($target->prestige * -(static::PRESTIGE_CHANGE_PERCENTAGE / 100)));

            // Reduce attacker prestige gain if the target was hit recently
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

            // todo: if wat war, increase $attackerPrestigeChange by +15%
        }

        if ($attackerPrestigeChange !== 0) {
            // todo: possible bug if all 12hr units die (somehow) and only 9hr units survive, since $units is input, not surviving units. fix?
            $slowestTroopsReturnHours = $this->getSlowestUnitReturnHours($dominion, $units);

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['prestige' => $attackerPrestigeChange],
                $slowestTroopsReturnHours
            );

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
        $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);
        $isOverwhelmed = $this->isOverwhelmed($dominion, $target, $units);
        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $targetDP = $this->getDefensivePowerWithTemples($dominion, $target);
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
            }
        } else {
            if ($isOverwhelmed) {
                $offensiveCasualtiesPercentage *= 2;
            }

            foreach ($units as $slot => $amount) {
                $unitsToKill = (int)ceil($amount * $offensiveCasualtiesPercentage);
                $offensiveUnitsLost[$slot] = $unitsToKill;
            }
        }

        foreach ($offensiveUnitsLost as $slot => &$amount) {
            // Reduce amount of units to kill by further multipliers
            $unitsToKillMultiplier = $this->casualtiesCalculator->getOffensiveCasualtiesMultiplierForUnitSlot($dominion, $slot, $isOverwhelmed);

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
     * @param array $units
     */
    protected function handleDefensiveCasualties(Dominion $dominion, Dominion $target, array $units): void
    {
        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $targetDP = $this->getDefensivePowerWithTemples($dominion, $target);
        $landRatio = ($this->rangeCalculator->getDominionRange($dominion, $target) / 100);
        $defensiveCasualtiesPercentage = (static::CASUALTIES_DEFENSIVE_BASE_PERCENTAGE / 100);

        // Modify casualties percentage based on relative land size
        $landRatioDiff = clamp(($landRatio - 1), -0.5, 0.5);
        $defensiveCasualtiesPercentage += ($landRatioDiff * static::CASUALTIES_DEFENSIVE_LAND_DIFFERENCE_ADD);

        // todo: unit reduce_combat_losses perk (eg dwarf cleric)

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
        $drafteesLost = (int)floor($target->military_draftees * $defensiveCasualtiesPercentage);
        $target->military_draftees -= $drafteesLost;

        $this->unitsLost += $drafteesLost; // todo: refactor
        $this->invasionResult['defender']['unitsLost']['draftees'] = $drafteesLost;

        // Non-draftees
        foreach ($target->race->units as $unit) {
            if ($unit->power_defense === 0.0) {
                continue;
            }

            // todo: unit specific fewer_casualties perk (eg human knight)

            $slotLost = (int)floor($target->{"military_unit{$unit->slot}"} * $defensiveCasualtiesPercentage);
            $defensiveUnitsLost[$unit->slot] = $slotLost;

            $this->unitsLost += $slotLost; // todo: refactor
        }

        foreach ($defensiveUnitsLost as $slot => $amount) {
            $target->{"military_unit{$slot}"} -= $amount;

            $this->invasionResult['defender']['unitsLost'][$slot] = $amount;
        }
    }

    /**
     * Handles land grabs and losses upon successful invasion.
     *
     * todo: description
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @throws Throwable
     */
    protected function handleLandGrabs(Dominion $dominion, Dominion $target, array $units): void
    {
        $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);

        // Nothing to grab if invasion isn't successful :^)
        if (!$isInvasionSuccessful) {
            return;
        }

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        $rangeMultiplier = ($range / 100);

        $landGrabRatio = 1;
        // todo: if mutual war, $landGrabRatio = 1.2
        // todo: if non-mutual war, $landGrabRatio = 1.15
        // todo: if peace, $landGrabRatio = 0.9
        $bonusLandRatio = 1.5;

        $attackerLandWithRatioModifier = ($this->landCalculator->getTotalLand($dominion) * $landGrabRatio);

        if ($range < 55) {
            $acresLost = (0.304 * ($rangeMultiplier ^ 2) - 0.227 * $rangeMultiplier + 0.048) * $attackerLandWithRatioModifier;
        } elseif ($range < 75) {
            $acresLost = (0.154 * $rangeMultiplier - 0.069) * $attackerLandWithRatioModifier;
        } else {
            $acresLost = (0.129 * $rangeMultiplier - 0.048) * $attackerLandWithRatioModifier;
        }

        $acresLost = (int)max(floor($acresLost), 10);

        $landLossRatio = ($acresLost / $this->landCalculator->getTotalLand($target));
        $landAndBuildingsLostPerLandType = $this->landCalculator->getLandLostByLandType($target, $landLossRatio);

//        $buildingsLostTemp = [];
        $landGainedPerLandType = [];
        foreach ($landAndBuildingsLostPerLandType as $landType => $landAndBuildingsLost) {
            $buildingsToDestroy = $landAndBuildingsLost['buildingsToDestroy'];
            $landLost = $landAndBuildingsLost['landLost'];
            $buildingsLostForLandType = $this->buildingCalculator->getBuildingTypesToDestroy($target, $buildingsToDestroy, $landType);
//            $buildingsLostTemp[$landType] = $buildingsLostForLandType;

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
            $landGained = ($landConquered + $landGenerated);

            $landGainedPerLandType["land_{$landType}"] = $landGained;

            if (!isset($this->invasionResult['attacker']['landConquered'])) {
                $this->invasionResult['attacker']['landConquered'] = [];
            }

            $this->invasionResult['attacker']['landConquered'][$landType] = $landConquered;

            if (!isset($this->invasionResult['attacker']['landGenerated'])) {
                $this->invasionResult['attacker']['landGenerated'] = [];
            }

            $this->invasionResult['attacker']['landGenerated'][$landType] = $landGenerated;
        }

        $this->landLost = $acresLost;

        $this->queueService->queueResources(
            'invasion',
            $dominion,
            $landGainedPerLandType
        );
    }

    /**
     * Handles morale changes for both attacker and defender.
     *
     * Target morale gets reduced by 5% after being on the receiving end of a
     * successful invasion.
     *
     * Attacker morale gets reduced by 5%, more so if they attack a target below
     * 75% range (up to 10% reduction at 40% target range).
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
    protected function handleMoraleChanges(Dominion $dominion, Dominion $target, array $units): void
    {
        $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);
        $range = $this->rangeCalculator->getDominionRange($dominion, $target);

        $dominion->morale -= 5;

        // Increased morale drops for attacking weaker targets
        if ($range < 75) {
            $additionalMoraleChange = max(round((((($range / 100) - 0.4) * 100) / 7) - 5), -5);
            $dominion->morale += $additionalMoraleChange;
        }

        if ($isInvasionSuccessful) {
            $target->morale -= 5;
        }
    }

    protected function handleConversions(Dominion $dominion, Dominion $target, array $units): void
    {
        // todo for later when I add spud/lycan
    }

    protected function handleUnitPerks(Dominion $dominion, Dominion $target, array $units): void
    {
        // todo: plunder
    }

    /**
     * Handles the surviving units returning home.
     *
     * @param Dominion $dominion
     * @param array $units
     * @throws Throwable
     */
    protected function handleReturningUnits(Dominion $dominion, array $units): void
    {
        foreach ($units as $slot => $amount) {
            $unitKey = "military_unit{$slot}";

            $dominion->$unitKey -= $amount;

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                [$unitKey => $amount],
                $this->getUnitReturnHoursForSlot($dominion, $slot)
            );
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
    protected function isInvasionSuccessful(Dominion $dominion, Dominion $target, array $units): bool
    {
        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $targetDP = $this->getDefensivePowerWithTemples($dominion, $target);

        return ($attackingForceOP > $targetDP);
    }

    /**
     * Check whether the attackers got overwhelmed by the target's defending army.
     *
     * Overwhelmed attackers have increased casualties, while the defending
     * party has reduced casualties.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return bool
     */
    protected function isOverwhelmed(Dominion $dominion, Dominion $target, array $units): bool
    {
        // Never overwhelm on successful invasions
        if ($this->isInvasionSuccessful($dominion, $target, $units)) {
            return false;
        }

        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $targetDP = $this->getDefensivePowerWithTemples($dominion, $target);

        return ((1 - $attackingForceOP / $targetDP) >= (static::OVERWHELMED_PERCENTAGE / 100));
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
        return ($this->getOPForUnits($dominion, $units) !== 0.0);
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

        return ($dominion->resource_boats >= ceil($unitsThatNeedBoats / static::UNITS_PER_BOAT));
    }

    /**
     * Check if an invasion passes the 33%-rule.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function passes33PercentRule(Dominion $dominion, array $units): bool
    {
        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $attackingForceDP = $this->getDPForUnits($dominion, $units);
        $currentHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion);
        $newHomeForcesDP = ($currentHomeForcesDP - $attackingForceDP);

        $minNewHomeForcesDP = (int)floor($attackingForceOP / 3);

        return ($newHomeForcesDP >= $minNewHomeForcesDP);
    }

    /**
     * Check if an invasion passes the 5:4-rule.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function passes54RatioRule(Dominion $dominion, array $units): bool
    {
        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $attackingForceDP = $this->getDPForUnits($dominion, $units);
        $currentHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion);
        $newHomeForcesDP = ($currentHomeForcesDP - $attackingForceDP);

        $attackingForceMaxOP = (int)ceil($newHomeForcesDP * 1.25);

        return ($attackingForceOP <= $attackingForceMaxOP);
    }

    /**
     * Get the modded OP for an array of units for a dominion.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return float
     */
    protected function getOPForUnits(Dominion $dominion, array $units): float
    {
        return ($this->getRawOPForUnits($dominion, $units) * $this->militaryCalculator->getOffensivePowerMultiplier($dominion));
    }

    /**
     * Get the raw OP for an array of units for a dominion.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return float
     */
    protected function getRawOPForUnits(Dominion $dominion, array $units): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            $op += ($unit->power_offense * (int)$units[$unit->slot]);
        }

        return $op;
    }

    /**
     * Get the modded DP for an array of units for a dominion.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return float
     */
    protected function getDPForUnits(Dominion $dominion, array $units): float
    {
        return ($this->getRawDPForUnits($dominion, $units) * $this->militaryCalculator->getDefensivePowerMultiplier($dominion));
    }

    /**
     * Get the raw DP for an array of units for a dominion.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return float
     */
    protected function getRawDPForUnits(Dominion $dominion, array $units): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            $op += ($unit->power_defense * (int)$units[$unit->slot]);
        }

        return $op;
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

        if (($unit->perkType !== null) && ($unit->perkType->key === 'faster_return')) {
            $hours -= (int)$unit->unit_perk_type_values;
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
        $hours = 12;

        foreach ($units as $slot => $amount) {
            if ($amount === 0) {
                continue;
            }

            $hoursForUnit = $this->getUnitReturnHoursForSlot($dominion, $slot);

            if ($hoursForUnit < $hours) {
                $hours = $hoursForUnit;
            }
        }

        return $hours;
    }

    protected function getDefensivePowerWithTemples(Dominion $dominion, Dominion $target): float
    {
        // Values (percentages)
        $dpReductionPerTemple = 1.5;
        $templeMaxDpReduction = 25;

        $dpMultiplierReduction = min(
            (($dpReductionPerTemple * $dominion->building_temple) / $this->landCalculator->getTotalLand($dominion)),
            ($templeMaxDpReduction / 100)
        );

        return $this->militaryCalculator->getDefensivePower($target, $dpMultiplierReduction);
    }
}
