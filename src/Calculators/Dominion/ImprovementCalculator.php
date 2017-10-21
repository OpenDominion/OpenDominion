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
    public function getImprovementMultiplier(Dominion $dominion, string $improvementType): float
    {
        $efficiencyPerMasonry = 2.75;

        $improvementPoints = $dominion->{'improvement_' . $improvementType};
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $multiplier = $this->getImprovementMaximum($improvementType)
            * (1 - exp(-$improvementPoints / ($this->getImprovementConstant($improvementType) * $totalLand + 15000)))
            * (1 + (($dominion->building_masonry * $efficiencyPerMasonry) / $totalLand));

        $multiplier *= 10000;
        $multiplier = round($multiplier);
        $multiplier /= 10000;

        return $multiplier;
    }

    /**
     * Returns the improvement maximum percentage.
     *
     * @param string $improvementType
     * @return float
     */
    protected function getImprovementMaximum(string $improvementType): float
    {
        $maxima = [
            'science' => 0.2,
            'keep' => 0.3,
            'towers' => 0.4,
            'forges' => 0.3,
            'walls' => 0.3,
            'harbor' => 0.4,
        ];

        return $maxima[$improvementType] ?: null;
    }

    /**
     * Returns the improvement calculation constant.
     *
     * A higher number makes it harder to reach higher improvement percentages.
     *
     * @param string $improvementType
     * @return int
     */
    protected function getImprovementConstant(string $improvementType): int
    {
        $modifiers = [
            'science' => 4000,
            'keep' => 4000,
            'towers' => 5000,
            'forges' => 7500,
            'walls' => 7500,
            'harbor' => 5000,
        ];

        return $modifiers[$improvementType] ?: null;
    }
}
