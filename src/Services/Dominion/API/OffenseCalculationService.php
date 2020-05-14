<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;

class OffenseCalculationService
{
    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        'race' => null,
        'op' => 0,
        'op_multiplier' => 0,
        'op_raw' => 0,
        'units' => [ // raw DP
            '1' => ['op' => 0],
            '2' => ['op' => 0],
            '3' => ['op' => 0],
            '4' => ['op' => 0],
        ],
    ];

    /**
     * OffenseCalculationService constructor.
     *
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     */
    public function __construct(
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator
    )
    {
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
    }

    /**
     * Calculates the total defense of a $dominion instance.
     *
     * @param Dominion $dominion
     * @param array $calc
     * @param Dominion $target
     * @param float $landRatio
     * @return array
     */
    public function calculate(Dominion $dominion, ?array $calc, ?Dominion $target, ?float $landRatio): array
    {
        // Sanitize input
        if($calc !== null) {
            $dominion->calc = $calc;
        }

        // Calculate unit stats
        foreach ($dominion->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                $target,
                $landRatio,
                $unit,
                'offense'
            );
        }

        // Calculate total offense
        $this->calculationResult['race'] = $dominion->race->id;
        $this->calculationResult['op_raw'] = $this->militaryCalculator->getOffensivePowerRaw($dominion, $target, $landRatio);
        $this->calculationResult['op_multiplier'] = ($this->militaryCalculator->getOffensivePowerMultiplier($dominion, $target) - 1) * 100;
        $this->calculationResult['op'] = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio);

        return $this->calculationResult;
    }
}
