<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ConstructionCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ConstructionCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    /**
     * Returns the Dominion's construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        $platinum = 0;

        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $platinum += max(
            (($totalLand < 250) ? 250 : $totalLand),
            (3 * $totalLand) / 4
        );

        $platinum -= 250;
        $platinum *= 1.53;
        $platinum += 850;

        $platinum *= $this->getCostMultiplier($dominion);

        return round($platinum);
    }

    /**
     * Returns the Dominion's construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCost(Dominion $dominion): int
    {
        $lumber = 0;

        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $lumber += max(
            (($totalLand < 250) ? 250 : $totalLand),
            (3 * $totalLand) / 4
        );

        $lumber -= 250;
        $lumber *= 0.35;
        $lumber += 87.5;

        $lumber *= $this->getCostMultiplier($dominion);

        return round($lumber);
    }

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        return min(
            floor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            floor($dominion->resource_lumber / $this->getLumberCost($dominion)),
            $this->landCalculator->getTotalBarrenLand($dominion)
        );
    }

    /**
     * Returns the Dominion's construction cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $factoryReduction = 4;
        $factoryReductionMax = 75;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        return (1 + $multiplier);
    }
}
