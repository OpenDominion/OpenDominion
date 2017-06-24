<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Contracts\Calculators\Dominion\Actions\ExplorationCalculator as ExplorationCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ExplorationCalculator implements ExplorationCalculatorContract
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ExplorationCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatinumCost(Dominion $dominion)
    {
        $platinum = 0;
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if ($totalLand < 300) {
            $platinum += -(3 * (300 - $totalLand));
        } else {
            $platinum += (3 * (($totalLand - 300) ** 1.09));
        }

        $platinum += 1000;
        $platinum *= 1.1;

        return (int)round($platinum);
    }

    /**
     * {@inheritdoc}
     */
    public function getDrafteeCost(Dominion $dominion)
    {
        $draftees = 0;
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if ($totalLand < 300) {
            $draftees = -(300 / $totalLand);
        } else {
            $draftees += (0.003 * (($totalLand - 300) ** 1.07));
        }

        $draftees += 5;
        $draftees *= 1.1;

        return (int)round($draftees);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxAfford(Dominion $dominion)
    {
        return (int)min(
            floor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            floor($dominion->military_draftees / $this->getDrafteeCost($dominion))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMoraleDrop($amount)
    {
        return (int)round(($amount + 2) / 3);
    }
}
