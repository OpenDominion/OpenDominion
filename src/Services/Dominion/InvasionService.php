<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class InvasionService
{
    /**
     * @var int The minimum morale required to initiate an invasion
     */
    protected const MIN_MORALE = 80;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var QueueService */
    protected $queueService;

    /**
     * InvasionService constructor.
     */
    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    /**
     * Check if dominion is sending out at least *some* OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    public function hasAnyOP(Dominion $dominion, array $units): bool
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
    public function allUnitsHaveOP(Dominion $dominion, array $units): bool
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
    public function hasEnoughUnitsAtHome(Dominion $dominion, array $units): bool
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
    public function hasEnoughBoats(Dominion $dominion, array $units): bool
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
     * Check if dominion has enough morale to initiate an invasion.
     * 
     */
    public function hasEnoughMorale(Dominion $dominion): bool
    {
       return ($dominion->morale >= static::MIN_MORALE);
    }

    /**
     * Check if an invasion passes the 33%-rule.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return bool
     */
    public function passes33PercentRule(Dominion $dominion, ?Dominion $target, array $units): bool
    {
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, null, $units);
        $attackingForceDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $units, 0, true);
        if ($attackingForceDP == 0) {
            return true;
        }
        $currentHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion);

        $unitsReturning = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $unitsReturning[$slot] = $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
        }

        $returningForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsReturning, 0, true);

        $totalDP = $currentHomeForcesDP + $returningForcesDP;

        $newHomeForcesDP = ($currentHomeForcesDP - $attackingForceDP);

        return ($newHomeForcesDP >= $totalDP * (1/3));
    }

    /**
     * Check if an invasion passes the 5:4-rule.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return bool
     */
    public function passes54RatioRule(Dominion $dominion, ?Dominion $target, ?float $landRatio, array $units): bool
    {
        $unitsHome = [
            0 => $dominion->military_draftees,
            1 => $dominion->military_unit1 - (isset($units[1]) ? $units[1] : 0),
            2 => $dominion->military_unit2 - (isset($units[2]) ? $units[2] : 0),
            3 => $dominion->military_unit3 - (isset($units[3]) ? $units[3] : 0),
            4 => $dominion->military_unit4 - (isset($units[4]) ? $units[4] : 0)
        ];
        $attackingForceOP = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);
        $newHomeForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome, 0, false, true);

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
    public function getUnitReturnHoursForSlot(Dominion $dominion, int $slot): int
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
    public function getSlowestUnitReturnHours(Dominion $dominion, array $units): int
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
}
