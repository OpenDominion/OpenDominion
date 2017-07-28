<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Contracts\Calculators\Dominion\Actions\RezoningCalculator as RezoningCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class RezoningCalculator implements RezoningCalculatorContract
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * RezoningCalculator constructor.
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
        return (int)round((($this->landCalculator->getTotalLand($dominion) - 250) * 0.6) + 250);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxAfford(Dominion $dominion)
    {
        // todo: factor in amount of barren land?
        return (int)floor($dominion->resource_platinum / $this->getPlatinumCost($dominion));
    }
}
