<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class RangeCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * RangeCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    public function isInRange(Dominion $self, Dominion $target): bool
    {
        // todo: if RG then $modifier = 0.6, else if EG then $modifier = 0.75, else $modifier = 0.6

        $selfLand = $this->landCalculator->getTotalLand($self);
        $targetLand = $this->landCalculator->getTotalLand($target);

        $modifier = 0.6;

        return (
            ($targetLand >= ($selfLand * $modifier)) &&
            ($targetLand <= ($selfLand / $modifier))
        );
    }
}
