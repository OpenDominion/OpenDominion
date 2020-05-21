<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class InvadeCalculationService
{
    /**
     * @var int How many units can fit in a single boat
     */
    protected const UNITS_PER_BOAT = 30;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        'boats_needed' => 0,
        'boats_remaining' => 0,
        'dp_multiplier' => 0,
        'op_multiplier' => 0,
        'away_defense' => 0,
        'away_offense' => 0,
        'home_defense' => 0,
        'home_offense' => 0,
        'home_dpa' => 0,
        'max_op' => 0,
        'min_dp' => 0,
        'land_ratio' => 0.5,
        'spell_bonus' => null,
        'units' => [ // home, away, raw OP, raw DP
            '1' => ['dp' => 0, 'op' => 0],
            '2' => ['dp' => 0, 'op' => 0],
            '3' => ['dp' => 0, 'op' => 0],
            '4' => ['dp' => 0, 'op' => 0],
        ],
    ];

    /**
     * InvadeActionService constructor.
     *
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param QueueService $queueService
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        QueueService $queueService,
        RangeCalculator $rangeCalculator
    )
    {
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->queueService = $queueService;
        $this->rangeCalculator = $rangeCalculator;
    }

    /**
     * Calculates an invasion against dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param array $units
     * @param array $calc
     * @return array
     */
    public function calculate(Dominion $dominion, ?Dominion $target, ?array $units, ?array $calc): array
    {
        if ($dominion->isLocked() || !$dominion->round->isActive()) {
            return ['result' => 'error', 'message' => 'invalid dominion(s) selected'];
        }

        if (empty($units)) {
            return ['result' => 'error', 'message' => 'invalid input'];
        }

        // Sanitize input
        $units = array_map('intval', array_filter($units));
        if($calc !== null) {
            $dominion->calc = $calc;
            $dominion->calc['invasion'] = true;
        }

        if ($target !== null) {
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
            $this->calculationResult['land_ratio'] = $landRatio;
        } else {
            $landRatio = 0.5;
        }

        // Calculate unit stats
        $unitsThatNeedBoats = 0;
        foreach ($dominion->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                $target,
                $landRatio,
                $unit,
                'defense'
            );
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                $target,
                $landRatio,
                $unit,
                'offense'
            );
            // Calculate boats needed
            if (isset($units[$unit->slot]) && $unit->need_boat) {
                $unitsThatNeedBoats += (int)$units[$unit->slot];
            }
        }
        $this->calculationResult['boats_needed'] = ceil($unitsThatNeedBoats / $dominion->race->getBoatCapacity());
        $this->calculationResult['boats_remaining'] = floor($dominion->resource_boats - $this->calculationResult['boats_needed']);

        // Calculate total offense and defense
        $this->calculationResult['dp_multiplier'] = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $this->calculationResult['op_multiplier'] = $this->militaryCalculator->getOffensivePowerMultiplier($dominion);

        $this->calculationResult['away_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $units);
        $this->calculationResult['away_offense'] = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units);

        $unitsHome = [
            0 => $dominion->military_draftees,
            1 => $dominion->military_unit1 - (isset($units[1]) ? $units[1] : 0),
            2 => $dominion->military_unit2 - (isset($units[2]) ? $units[2] : 0),
            3 => $dominion->military_unit3 - (isset($units[3]) ? $units[3] : 0),
            4 => $dominion->military_unit4 - (isset($units[4]) ? $units[4] : 0)
        ];

        $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome, 0, false, true);
        $this->calculationResult['home_offense'] = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $unitsHome);
        $this->calculationResult['home_dpa'] = $this->calculationResult['home_defense'] / $this->landCalculator->getTotalLand($dominion);

        // Calculate returning defense
        $unitsReturning = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $unitsReturning[$slot] = $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
        }
        $returningForcesDP = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsReturning, 0, true);
        $homeForcesDP = $this->militaryCalculator->getDefensivePower($dominion);

        $this->calculationResult['max_op'] = $this->calculationResult['home_defense'] * 1.25;
        $this->calculationResult['min_dp'] = ($returningForcesDP + $homeForcesDP) / 3;

        $this->calculationResult['target_min_dp'] = $this->militaryCalculator->getMinimumDefense($target);

        return $this->calculationResult;
    }
}
