<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Interfaces\Calculators\Dominion\Actions\ConstructionCalculatorInterface;
use OpenDominion\Models\Dominion;

class ConstructionCalculator implements ConstructionCalculatorInterface
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ConstructionCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     */
    public function __construct(BuildingCalculator $buildingCalculator, LandCalculator $landCalculator)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatinumCost(Dominion $dominion)
    {
        // todo: refactor calcs and remove these lines
        $this->buildingCalculator->setDominion($dominion);
        $this->landCalculator->setDominion($dominion);

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
     * {@inheritdoc}
     */
    public function getLumberCost(Dominion $dominion)
    {
        // todo: refactor calcs and remove these lines
        $this->buildingCalculator->setDominion($dominion);
        $this->landCalculator->setDominion($dominion);

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
     * {@inheritdoc}
     */
    public function getMaxAfford(Dominion $dominion)
    {
        // todo: refactor calcs and remove these lines
        $this->buildingCalculator->setDominion($dominion);
        $this->landCalculator->setDominion($dominion);

        // todo: check if round() is needed
        return (int)round(min(
            floor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            floor($dominion->resource_lumber / $this->getLumberCost($dominion))
        ));
    }
}
