<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class DestroyActionService
{
    use DominionGuardsTrait;

    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws RuntimeException
     */
    public function destroy(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('intval', $data);

        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy === 0) {
            throw new RuntimeException('The destruction was not completed due to bad input.');
        }

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount > $dominion->{'building_' . $buildingType}) {
                throw new RuntimeException('The destruction was not completed due to bad input.');
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
