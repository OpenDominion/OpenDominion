<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
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

        $data = array_map('\intval', $data);

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
            $dominion->decrement('building_' . $buildingType, $amount);
        }

        $dominion->save(['event' => HistoryService::EVENT_ACTION_DESTROY]);

        return [
            'message' => sprintf(
                'Destruction of %s %s is complete.',
                number_format($totalBuildingsToDestroy),
                str_plural('building', $totalBuildingsToDestroy)
            ),
            'data' => [
                'totalBuildingsDestroyed' => $totalBuildingsToDestroy,
            ],
        ];
    }
}
