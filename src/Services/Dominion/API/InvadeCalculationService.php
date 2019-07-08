<?php

namespace OpenDominion\Services\Dominion\API;

//use OpenDominion\Calculators\Dominion\BuildingCalculator;
//use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
//use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
//use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
//use OpenDominion\Models\Unit;
use RuntimeException;
use Throwable;

class InvadeCalculationService
{
    /**
     * @var int How many units can fit in a single boat
     */
    protected const UNITS_PER_BOAT = 30;

    /** @var BuildingCalculator */
    //protected $buildingCalculator;

    /** @var LandCalculator */
    //protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var SpellCalculator */
    //protected $spellCalculator;

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        'boats_needed' => 0,
        'dp_multiplier' => null,
        'op_multiplier' => null,
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
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     * @param SpellCalculator $spellCalculator
     */
    public function __construct(
        //BuildingCalculator $buildingCalculator,
        //LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        RangeCalculator $rangeCalculator
        //SpellCalculator $spellCalculator
        )
    {
        //$this->buildingCalculator = $buildingCalculator;
        //$this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->rangeCalculator = $rangeCalculator;
        //$this->spellCalculator = $spellCalculator;
    }

    /**
     * Calculates an invasion against dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws Throwable
     */
    public function calculate(Dominion $dominion, Dominion $target = null, array $units): array
    {
        // Sanitize input
        $units = array_map('intval', array_filter($units));

        //if (!$this->hasEnoughBoats($dominion, $units)) {
        //    throw new RuntimeException('You do not have enough boats to send this many units');
        //}

        if($target) {
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
            $this->calculationResult['land_ratio'] = $landRatio;
        } else {
            $landRatio = 0.5;
        }

        $this->calculationResult['dp_multiplier'] = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $this->calculationResult['op_multiplier'] = $this->militaryCalculator->getOffensivePowerMultiplier($dominion);

        foreach ($dominion->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'defense');
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'offense');
        }

        return $this->calculationResult;
    }
}
