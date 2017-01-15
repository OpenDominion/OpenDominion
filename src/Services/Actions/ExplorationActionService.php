<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ExplorationActionService extends AbstractActionService
{
    /** @var LandCalculator */
    protected $landCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->landCalculator = app()->make(LandCalculator::class, [$dominion]);
    }

    /**
     * Returns the Dominion's exploration platinum cost per acre.
     *
     * @return int
     */
    public function getPlatinumCost()
    {
        $platinum = 0;
        $totalLand = $this->landCalculator->getTotalLand();

        if ($totalLand < 300) {
            $platinum += -(3 * (300 - $totalLand));
        } else {
            $platinum += (3 * pow(($totalLand - 300), 1.09));
        }

        $platinum += 1000;
        $platinum *= 1.1;

        return (int)round($platinum);
    }

    /**
     * Returns the Dominion's exploration draftee cost per acre.
     *
     * @return int
     */
    public function getDrafteeCost()
    {
        $draftees = 0;
        $totalLand = $this->landCalculator->getTotalLand();

        if ($totalLand < 300) {
            $draftees = -(300 / $totalLand);
        } else {
            $draftees += (0.003 * pow(($totalLand - 300), 1.07));
        }

        $draftees += 5;
        $draftees *= 1.1;

        return (int)round($draftees);
    }

    /**
     * Returns the maximum number of acres a Dominion can afford.
     *
     * @return int
     */
    public function getMaxAfford()
    {
        return (int)round(min(
            floor($this->dominion->resource_platinum / $this->getPlatinumCost()),
            floor($this->dominion->military_draftees / $this->getDrafteeCost())
        ));
    }

    /**
     * Returns the Dominion's morale drop after exploring for $amount of acres.
     *
     * @param $amount
     * @return int
     */
    public function getMoraleDrop($amount)
    {
        return (int)round(($amount + 2) / 3);
    }
}
