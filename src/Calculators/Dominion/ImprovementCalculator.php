<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ImprovementCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ImprovementCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    /**
     * Returns the Dominion's improvement multiplier for a given improvement type.
     *
     * @param Dominion $dominion
     * @param string $improvementType
     * @return float
     */
    public function getImprovementMultiplierBonus(Dominion $dominion, string $improvementType): float
    {
        $efficiencyPerMasonry = 2.75;

        $improvementPoints = $dominion->{'improvement_' . $improvementType};
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $multiplier = $this->getImprovementMaximum($improvementType)
            * (1 - exp(-$improvementPoints / ($this->getImprovementCoefficient($improvementType) * $totalLand + 15000)))
            * (1 + (($dominion->building_masonry * $efficiencyPerMasonry) / $totalLand));

        return round($multiplier, 4);
    }

    /**
     * Returns the improvement maximum percentage.
     *
     * @param string $improvementType
     * @return float
     */
    protected function getImprovementMaximum(string $improvementType): float
    {
        $maximumPercentages = [
            'science' => 20,
            'keep' => 30,
            'towers' => 40,
            'forges' => 30,
            'walls' => 30,
            'harbor' => 40,
        ];

        return (($maximumPercentages[$improvementType] / 100) ?: null);
    }

    /**
     * Returns the improvement calculation coefficient.
     *
     * A higher number makes it harder to reach higher improvement percentages.
     *
     * @param string $improvementType
     * @return int
     */
    protected function getImprovementCoefficient(string $improvementType): int
    {
        $coefficients = [
            'science' => 4000,
            'keep' => 4000,
            'towers' => 4000,
            'forges' => 7500,
            'walls' => 7500,
            'harbor' => 5000,
        ];

        return ($coefficients[$improvementType] ?: null);
    }
}
