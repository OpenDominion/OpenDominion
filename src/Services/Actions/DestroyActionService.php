<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class DestroyActionService
{
    use DominionGuardsTrait;

    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws DominionLockedException
     * @throws BadInputException
     */
    public function destroy(Dominion $dominion, array $data)
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('intval', $data);

        /** @var BuildingCalculator $buildingCalculator */
        $buildingCalculator = app(BuildingCalculator::class)
            ->init($dominion);

        /** @var LandCalculator $landCalculator */
        $landCalculator = app(LandCalculator::class)
            ->init($dominion);

        /** @var LandHelper $landHelper */
        $landHelper = app(LandHelper::class);

        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy === 0) {
            throw new BadInputException;
        }

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount > $dominion->{'building_' . $buildingType}) {
                throw new BadInputException;
            }
        }

        foreach ($data as $buildingType => $amount) {
            $dominion->{'building_' . $buildingType} -= $amount;
        }

        $dominion->save();

        return [
            'totalBuildingsDestroyed' => $totalBuildingsToDestroy,
        ];
    }
}
