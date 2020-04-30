<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;

class DefenseCalculationService
{
    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        'race' => null,
        'dp' => 0,
        'dp_multiplier' => 0,
        'dp_raw' => 0,
        'units' => [ // raw DP
            '1' => ['dp' => 0],
            '2' => ['dp' => 0],
            '3' => ['dp' => 0],
            '4' => ['dp' => 0],
        ],
    ];

    /**
     * InvadeActionService constructor.
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
     * @return array
     */
    public function calculate(Dominion $dominion, ?array $calc): array
    {
        // Sanitize input
        if($calc !== null) {
            $dominion->calc = $calc;
        }

        // Calculate unit stats
        $unitsThatNeedBoats = 0;
        foreach ($dominion->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                null,
                null,
                $unit,
                'defense'
            );
        }

        // Calculate total defense
        $this->calculationResult['race'] = $dominion->race->id;
        $this->calculationResult['temple_reduction'] = $this->militaryCalculator->getTempleReduction($dominion);
        $this->calculationResult['dp_raw'] = $this->militaryCalculator->getDefensivePowerRaw($dominion);
        $this->calculationResult['dp_multiplier'] = ($this->militaryCalculator->getDefensivePowerMultiplier($dominion, $this->militaryCalculator->getTempleReduction($dominion)) - 1) * 100;
        $this->calculationResult['dp'] = $this->militaryCalculator->getDefensivePower($dominion);

        return $this->calculationResult;
    }
}
