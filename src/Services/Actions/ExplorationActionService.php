<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ExplorationActionService extends AbstractActionService
{
//    /** @var LandCalculator */
//    protected $landCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

//        $this->landCalculator = app()->make(LandCalculator::class, [$dominion]);
    }

    public function explore(array $data)
    {
        // todo
    }
}
