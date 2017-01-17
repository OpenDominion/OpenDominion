<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\DominionQueueService;

class LandCalculator extends AbstractDominionCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandHelper */
    protected $landHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var DominionQueueService */
    protected $dominionQueueService;

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        parent::init($dominion);

        $this->buildingHelper = app()->make(BuildingHelper::class);
        $this->landHelper = app()->make(LandHelper::class);
        $this->buildingCalculator = app()->make(BuildingCalculator::class)->setDominion($dominion);
        $this->dominionQueueService = app()->make(DominionQueueService::class, [$dominion]);
    }

    /**
     * Returns the Dominion's total acres of land.
     *
     * @return int
     */
    public function getTotalLand()
    {
        $totalLand = 0;

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $totalLand += $this->dominion->{'land_' . $landType};
        }

        return $totalLand;
    }

    /**
     * Returns the Dominion's total acres of barren land.
     *
     * @return int
     */
    public function getTotalBarrenLand()
    {
        return ($this->getTotalLand() - $this->buildingCalculator->getTotalBuildings() - $this->dominionQueueService->getExplorationQueueTotal());
    }

    /**
     * Returns the Dominion's total barren land by land type.
     *
     * @param string $landType
     * @return int
     */
    public function getTotalBarrenLandByLandType($landType)
    {
        return $this->getBarrenLandByLandType()[$landType];
    }

    /**
     * Returns the Dominion's barren land by land type.
     *
     * @return int[]
     */
    public function getBarrenLandByLandType()
    {
        $buildingTypesbyLandType = $this->buildingHelper->getBuildingTypesByLandType($this->dominion->race);

        $return = [];

        foreach ($buildingTypesbyLandType as $landType => $buildingTypes) {
            $barrenLand = $this->dominion->{'land_' . $landType};

            foreach ($buildingTypes as $buildingType) {
                $barrenLand -= $this->dominion->{'building_' . $buildingType};
            }

            // todo: construction queue

            $return[$landType] = $barrenLand;
        }

        return $return;
    }

    /**
     * Returns the Dominion's exploration platinum cost per acre.
     *
     * @return int
     */
    public function getExplorationPlatinumCost()
    {
        $platinum = 0;
        $totalLand = $this->getTotalLand();

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
    public function getExplorationDrafteeCost()
    {
        $draftees = 0;
        $totalLand = $this->getTotalLand();

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
    public function getExplorationMaxAfford()
    {
        return (int)round(min(
            floor($this->dominion->resource_platinum / $this->getExplorationPlatinumCost()),
            floor($this->dominion->military_draftees / $this->getExplorationDrafteeCost())
        ));
    }

    /**
     * Returns the Dominion's morale drop after exploring for $amount of acres.
     *
     * @param $amount
     * @return int
     */
    public function getExplorationMoraleDrop($amount)
    {
        return (int)round(($amount + 2) / 3);
    }
}
