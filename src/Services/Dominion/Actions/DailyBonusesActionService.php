<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class DailyBonusesActionService
{
    use DominionGuardsTrait;

    /**
     * Claims the daily platinum bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws RuntimeException
     */
    public function claimPlatinum(Dominion $dominion): array
    {
        if ($dominion->daily_platinum) {
            throw new RuntimeException('You already claimed your platinum bonus for today.');
        }

        $platinumGained = $dominion->peasants * 4;
        $dominion->resource_platinum += $platinumGained;
        $dominion->daily_platinum = true;
        $dominion->save();

        return [
            'message' => sprintf('You gained %s platinum.', $platinumGained),
            'data' => [
                'platinumGained' => $platinumGained,
            ],
        ];
    }

    /**
     * Claims the daily land bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws RuntimeException
     */
    public function claimLand(Dominion $dominion): array
    {
        if ($dominion->daily_land) {
            throw new RuntimeException('You already claimed your land bonus for today.');
        }

        $landGained = 20;
        $attribute = 'land_' . $dominion->race->home_land_type;
        $dominion->{$attribute} += $landGained;
        $dominion->daily_land = true;
        $dominion->save();

        return [
            'message' => sprintf('You gained %d %s land.', $landGained, $dominion->race->home_land_type),
            'data' => [
                'landGained' => $landGained,
            ],
        ];
    }
}
