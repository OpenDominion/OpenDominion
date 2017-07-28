<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Services\Actions\RezoneActionServiceContract;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class RezoneActionService implements RezoneActionServiceContract
{
    use DominionGuardsTrait;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * RezoneActionService constructor.
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
            return 0;
        }

        // Check if the requested amount of land is barren.
        foreach ($remove as $landType => $landToRemove) {
            $landAvailable = $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType);
            if ($landToRemove > $landAvailable) {
                throw new NotEnoughResourcesException('Can only rezone ' . $landAvailable . ' ' . str_plural($landType, $landAvailable));
            }
        }

        $costPerAcre = $this->landCalculator->getRezoningPlatinumCost($dominion); // todo: fix this
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

        $dominion->save();

        return $totalCost;
    }
}
