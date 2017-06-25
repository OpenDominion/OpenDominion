<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
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
