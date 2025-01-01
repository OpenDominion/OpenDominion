<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ImprovementCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ImprovementCalculator constructor.
     */
    public function __construct(
        HeroCalculator $heroCalculator,
        LandCalculator $landCalculator
    )
    {
        $this->heroCalculator = $heroCalculator;
        $this->landCalculator = $landCalculator;
    }

    /**
     * Returns the Dominion's improvement multiplier for a given improvement type.
     *
     * @param Dominion $dominion
     * @param string $improvementType
     * @return float
     */
    public function getImprovementMultiplierBonus(Dominion $dominion, string $improvementType, bool $secondary = false): float
    {
        $improvementPoints = $dominion->{'improvement_' . $improvementType};
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $multiplier = $this->getImprovementMaximum($improvementType)
            * (1 - exp(-$improvementPoints / ($this->getImprovementCoefficient($improvementType) * $totalLand + 15000)));

        // Ignores Masonries for Protection
        if (!($secondary && ($improvementType == 'spires' || $improvementType == 'harbor'))) {
            $multiplier *= $this->getImprovementMultiplier($dominion);
        }

        // Bonus and Cap for Protection
        if ($secondary) {
            if ($improvementType == 'spires' || $improvementType == 'harbor') {
                $multiplier = min(0.5, $multiplier * 1.5);
            }
        }

        return round($multiplier, 4);
    }

    /**
     * Returns the Dominion's improvement multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getImprovementMultiplier(Dominion $dominion): float
    {
        $efficiencyPerMasonry = 2.6;
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        return (1 + (($dominion->building_masonry * $efficiencyPerMasonry) / $totalLand));
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
            'spires' => 60,
            'forges' => 30,
            'walls' => 30,
            'harbor' => 60,
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
            'spires' => 5000,
            'forges' => 7500,
            'walls' => 7500,
            'harbor' => 5000,
        ];

        return ($coefficients[$improvementType] ?: null);
    }

    /**
     * Returns the improvement total for a dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getImprovementTotal(Dominion $dominion): int
    {
        return (
            $dominion->improvement_science +
            $dominion->improvement_keep +
            $dominion->improvement_spires +
            $dominion->improvement_forges +
            $dominion->improvement_walls +
            $dominion->improvement_harbor
        );
    }

    /**
     * Returns the Dominion's investment multiplier.
     *
     * @param Dominion $dominion
     * @param string $resource
     * @param string $improvementType
     * @return float
     */
    public function getInvestmentMultiplier(Dominion $dominion, string $resource = '', string $improvementType = ''): float
    {
        $multiplier = 1;

        // Racial bonus multiplier
        $multiplier += $dominion->race->getPerkMultiplier('invest_bonus');
        $multiplier += $dominion->race->getPerkMultiplier("invest_bonus_{$resource}");

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier("invest_bonus_{$improvementType}");

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'invest_bonus');
        if ($dominion->hero !== null) {
            $multiplier += $dominion->hero->getPerkMultiplier('invest_bonus');
        }

        // Wonder
        $multiplier += $dominion->getWonderPerkMultiplier('invest_bonus');

        return $multiplier;
    }
}
