<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ConstructionActionService extends AbstractActionService
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->buildingCalculator = app()->make(BuildingCalculator::class, [$dominion]);
        $this->landCalculator = app()->make(LandCalculator::class, [$dominion]);
    }

    /**
     * Returns the Dominion's construction platinum cost per building.
     *
     * @return int
     */
    public function getPlatinumCost()
    {
        $platinum = 0;
        $totalBuildings = $this->buildingCalculator->getTotalBuildings();
        $totalLand = $this->landCalculator->getTotalLand();

        if ($totalBuildings >= 1250) {
            $platinum += max(
                ($totalLand * 0.75),
                $totalBuildings
            );
        } else {
            $platinum += $totalLand;
        }

        $platinum -= 250;
        $platinum *= 1.53;
        $platinum += 850;

        return (int)round($platinum);
    }

    /**
     * Returns the Dominion's construction lumber cost per building.
     *
     * @return int
     */
    public function getLumberCost()
    {
        $lumber = 0;
        $totalBuildings = $this->buildingCalculator->getTotalBuildings();
        $totalLand = $this->landCalculator->getTotalLand();

        if ($totalBuildings >= 1250) {
            $lumber += max(
                ($totalLand * 0.75),
                $totalBuildings
            );
        } else {
            $lumber += $totalLand;
        }

        $lumber -= 250;
        $lumber *= 0.6;
        $lumber += 88;

        return (int)round($lumber);
    }

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @return int
     */
    public function getMaxAfford()
    {
        return (int)round(min(
            floor($this->dominion->resource_platinum / $this->getPlatinumCost()),
            floor($this->dominion->resource_lumber / $this->getLumberCost())
        ));
    }
}
