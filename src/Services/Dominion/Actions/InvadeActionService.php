<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
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

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var QueueService */
    protected $queueService;

    /**
     * InvadeActionService constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param ProtectionService $protectionService
     * @param RangeCalculator $rangeCalculator
     * @param QueueService $queueService
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        ProtectionService $protectionService,
        RangeCalculator $rangeCalculator,
        QueueService $queueService)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
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
            $this->handlePrestigeChanges($dominion, $target, $units);
            $this->handleOffensiveCasualties($dominion, $target, $units);
            $this->handleDefensiveCasualties($dominion, $target, $units);
            $this->handleLandGrabs($dominion, $target, $units);
            $this->handleLandLosses($dominion, $target, $units);
            $this->handleMoraleChanges($dominion, $target, $units);
            $this->handleConversions($dominion, $target, $units);
            $this->handleUnitPerks($dominion, $target, $units);
            // todo: insert queues for returning units

            // todo: add notification for $target

            // todo: create event
            // todo: post to both TCs

            dd('foo ');

            // todo: refactor everything below this line

            $landRatio = ($this->rangeCalculator->getDominionRange($dominion, $target) / 100);
            $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);

            // LAND GAINS/LOSSES

            // if $invasionSuccessful
                // landGrabRatio = 1.0
                // if mutual war, landGrabRatio = 1.2
                // if non-mutual war, landGrabRatio = 1.15
                // if war and peace, landGrabRatio = 1
                // if peace, landGrabRatio = 0.9

                // calculate total acres of land lost. FORMULA:
                /*
                // max(
                //     floor(
                //         if(landRatio<0.55) then
                //             (0.304*landRatio^2-0.227*landRatio+0.048)*attackerLand*landGrabRatio
                //         elseif(landRatio<0.75) then
                //             attackerLand*landGrabRatio*(0.154*landRatio - 0.069)
                //         else
                //             landGrabRatio*attackerLand*(0.129*landRatio-0.048)
                //     ,1)
                // ,10)
                 */

                // calculate target barren land losses (array)
                // calculate target buildings destroyed (array), only if target does not have enough barren land buffer, in ratio of buildings constructed per land type
                // calculate total conquered acres (same acres as target land lost)
                // calculate land conquers (array) (= target land loss)
                // calculate extra land generated (array) (always 50% of conquered land, even ratio across all 7 land types) (needs confirmation)

            if($isInvasionSuccessful) {
                $landGrabRatio = 1;
                $bonusLandRatio = 1.5;
                // TODO: check for war/peace
                $attackerLandWithRatioModifier = $this->landCalculator->getTotalLand($dominion) * $landGrabRatio;

                $landLossPercentage = 0;
                if($landRatio < 0.55) {
                    $landLossPercentage = (0.304 * $landRatio ^ 2 - 0.227 * $landRatio + 0.048) * $attackerLandWithRatioModifier;
                } elseif($landRatio < 0.75) {
                    $landLossPercentage = (0.154 * $landRatio - 0.069) * $attackerLandWithRatioModifier;
                } else {
                    $landLossPercentage = (0.129 * $landRatio - 0.048) * $attackerLandWithRatioModifier;
                }

                $landLossPercentage = floor($landLossPercentage);

                $landLossPercentage = min(max($landLossPercentage, 10), 15);

                $landLossRatio = $landLossPercentage / 100;

                $landAndBuildingsLostPerLandType = $this->landCalculator->getLandLostByLandType($target, $landLossRatio);

                $buildingsLostTemp = [];
                $landGainedPerLandType = [];
                foreach($landAndBuildingsLostPerLandType as $landType => $landAndBuildingsLost) {
                    $buildingsToDestroy = $landAndBuildingsLost['buildingsToDestroy'];
                    $landLost = $landAndBuildingsLost['landLost'];
                    $buildingsLostForLandType = $this->buildingCalculator->getBuildingTypesToDestroy($target, $buildingsToDestroy, $landType);
                    $buildingsLostTemp[$landType] = $buildingsLostForLandType;

                    // Remove land
                    $target->{'land_' . $landType} -= $landLost;
                    // Destroy buildings
                    foreach($buildingsLostForLandType as $buildingType => $buildingsLost) {
                        $builtBuildingsToDestroy = $buildingsLost['builtBuildingsToDestroy'];
                        $resourceName = "building_{$buildingType}";
                        $target->$resourceName -= $builtBuildingsToDestroy;

                        $buildingsInQueueToRemove = $buildingsLost['buildingsInQueueToRemove'];
                        $this->queueService->dequeueResource('construction', $target, $resourceName, $buildingsInQueueToRemove);
                    }

                    $landGained = round($landLost * $bonusLandRatio);
                    $landGainedPerLandType["land_{$landType}"] = $landGained;
                }

                $this->queueService->queueResources('invasion', $dominion, $landGainedPerLandType);
            }

            // MORALE

            // >= 75%+ size: reduce -5% self morale
            // else < 75% size: reduce morale, linear scale from -5% morale at 75% size to -10%  morale at 40% size
            // if $invasionSuccessful: reduce target morale by -5%
            $dominion->morale -= 5;
            if($landRatio < 0.75) {
                $additionalMoraleLoss = max(round(((($landRatio - 0.4) * 100) / 7) - 5), -5);
                $dominion->morale += $additionalMoraleLoss;
            }

            if($isInvasionSuccessful) {
                $target->morale -= 5;
            }

            // MISC

            // if $invasionSuccessful
                // hobbos and other special units that trigger something upon invading
                // later: converts

            // insert queues for returning units, incoming land and incoming prestige
            // send notification to $target
            // todo: post to both TCs

            // shit for elsewhere:

            // todo: show message in Clear Sight at the bottom for dominions that have been invaded too much recently:
                // 1-2 times: "This dominion has been invaded in recent times"
                // 3-4 times: "This dominion has been invaded heavily in recent times"
                // 5+ times: "This dominion has been invaded extremely heavily in recent times"

            // todo: add battle reports table/mechanic
            $target->save();
            $dominion->save();
        });

        return [];
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

            // todo: Also needs displaying on the invade page itself if a target got invaded (heavily etc) recently

            // todo: if wat war, increase $attackerPrestigeChange by +15%
        }

        if ($attackerPrestigeChange !== 0) {
            $slowestTroopsReturnHours = 9; // todo: implement this

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['prestige' => $attackerPrestigeChange],
                $slowestTroopsReturnHours
            );
        }

        if ($targetPrestigeChange !== 0) {
            $target->prestige += $targetPrestigeChange;
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
     */
    protected function handleOffensiveCasualties(Dominion $dominion, Dominion $target, array $units): void
    {
        $isInvasionSuccessful = $this->isInvasionSuccessful($dominion, $target, $units);
        $isOverwhelmed = $this->isOverwhelmed($dominion, $target, $units);
        $attackingForceOP = $this->getOPForUnits($dominion, $units);
        $targetDP = $this->militaryCalculator->getDefensivePower($target);
        $offensiveCasualtiesPercentage = (static::CASUALTIES_OFFENSIVE_BASE_PERCENTAGE / 100);

        // todo: global offensive casualty reduction, step 1: non-unit bonuses (Healer hero, shrines, tech, wonders) (capped at -80% casualties)
        // todo: global offensive casualty reduction, step 2: unit bonuses (cleric/shaman, later firewalkers etc) (multiplicative with step 1)

        $offensiveUnitsLost = [];

        if ($isInvasionSuccessful) {
            $totalUnitsSent = 0;
            foreach ($units as $amount) {
                $totalUnitsSent += $amount; // todo: refactor to array_sum($units)?
            }

            $averageOPPerUnitSent = ($attackingForceOP / $totalUnitsSent);
            $OPNeededToBreakTarget = ($targetDP + 1);
            $unitsNeededToBreakTarget = round($OPNeededToBreakTarget / $averageOPPerUnitSent);

            $totalUnitsLeftToKill = ceil($unitsNeededToBreakTarget * $offensiveCasualtiesPercentage);

            foreach ($units as $slot => $amount) {
                $slotTotalAmountPercentage = ($amount / $totalUnitsSent);

                if ($slotTotalAmountPercentage === 0) {
                    continue;
                }

                // todo: unit specific fewer_casualties perk (eg human knight, firewalker salamander)

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

        foreach ($offensiveUnitsLost as $slot => $amount) {
            $dominion->{"military_unit{$slot}"} -= $amount;
        }
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
        $targetDP = $this->militaryCalculator->getDefensivePower($target);
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

        // Non-draftees
        foreach ($target->race->units as $unit) {
            if ($unit->power_defense === 0.0) {
                continue;
            }

            $slotLost = (int)floor($target->{"military_unit{$unit->slot}"} * $defensiveCasualtiesPercentage);
            $defensiveUnitsLost[$unit->slot] = $slotLost;
        }

        foreach ($defensiveUnitsLost as $slot => $amount) {
            $target->{"military_unit{$slot}"} -= $amount;
        }

        // Draftees
        $target->military_draftees -= (int)floor($target->military_draftees * $defensiveCasualtiesPercentage);
    }

    protected function handleLandGrabs(Dominion $dominion, Dominion $target, array $units): void
    {

    }

    protected function handleLandLosses(Dominion $dominion, Dominion $target, array $units): void
    {
        //
    }

    protected function handleMoraleChanges(Dominion $dominion, Dominion $target, array $units): void
    {
        //
    }

    protected function handleConversions(Dominion $dominion, Dominion $target, array $units): void
    {
        //
    }

    protected function handleUnitPerks(Dominion $dominion, Dominion $target, array $units): void
    {
        //
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
        $targetDP = $this->militaryCalculator->getDefensivePower($target);

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
        $targetDP = $this->militaryCalculator->getDefensivePower($target);

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
}
