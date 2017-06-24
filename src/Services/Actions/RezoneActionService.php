<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Interfaces\Calculators\Dominion\LandCalculatorInterface;
use OpenDominion\Interfaces\Services\Actions\RezoneActionServiceInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class RezoneActionService implements RezoneActionServiceInterface
{
    use DominionGuardsTrait;

    /** @var LandCalculatorInterface */
    protected $landCalculator;

    /**
     * RezoneActionService constructor.
     *
     * @param LandCalculatorInterface $landCalculator
     */
    public function __construct(LandCalculatorInterface $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    /**
     * {@inheritdoc}
     */
    public function rezone(Dominion $dominion, array $remove, array $add)
    {
        $this->guardLockedDominion($dominion);

        // Level out rezoning going to the same type.
        foreach (array_intersect_key($remove, $add) as $key => $value) {
            $sub = min($value, $add[$key]);
            $remove[$key] -= $sub;
            $add[$key] -= $sub;
        }

        // Filter out empties.
        $remove = array_filter($remove);
        $add = array_filter($add);

        $totalLand = array_sum($remove);

        if ($totalLand !== array_sum($add)) {
            throw new BadInputException('Rezoning must remove and add equal amounts of land.');
        }

        if ($totalLand === 0) {
            // Nothing to do.
            return;
        }

        // Check if the requested amount of land is barren.
        foreach ($remove as $landType => $landToRemove) {
            $landAvailable = $this->landCalculator->getTotalBarrenLandByLandType($landType);
            if ($landToRemove > $landAvailable) {
                throw new NotEnoughResourcesException('Can only rezone ' . $landAvailable . ' ' . $landType);
            }
        }


        $this->landCalculator->init($dominion);
        $costPerAcre = $this->landCalculator->getRezoningPlatinumCost($dominion);
        $totalCost = $totalLand * $costPerAcre;

        if ($totalCost > $dominion->resource_platinum) {
            throw new NotEnoughResourcesException('Not enough platinum.');
        }

        // All fine, perform changes.
        $dominion->resource_platinum -= $totalCost;

        foreach ($remove as $landType => $amount) {
            $dominion->{'land_' . $landType} -= $amount;
        }
        foreach ($add as $landType => $amount) {
            $dominion->{'land_' . $landType} += $amount;
        }


    }

}
