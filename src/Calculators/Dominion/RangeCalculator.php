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
        //
    }
}
