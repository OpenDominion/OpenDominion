<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ConstructionActionService extends AbstractActionService
{
//    /** @var BuildingCalculator */
//    protected $buildingCalculator;

//    /** @var LandCalculator */
//    protected $landCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

//        $this->buildingCalculator = app()->make(BuildingCalculator::class, [$dominion]);
//        $this->landCalculator = app()->make(LandCalculator::class, [$dominion]);
    }

    public function construct(array $data)
    {
        // todo
    }
}
