<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Contracts\Services\Dominion\Actions\DestroyActionService as DestroyActionServiceContract;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class DestroyActionService implements DestroyActionServiceContract
{
    use DominionGuardsTrait;

    /**
     * {@inheritdoc}
     */
    public function destroy(Dominion $dominion, array $data)
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
