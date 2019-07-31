<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class DailyBonusesActionService
{
    use DominionGuardsTrait;

    /**
     * Claims the daily platinum bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function claimPlatinum(Dominion $dominion): array
    {
        if ($dominion->daily_platinum) {
            throw new GameException('You already claimed your platinum bonus for today.');
        }

        $platinumGained = $dominion->peasants * 4;
        $dominion->increment('resource_platinum', $platinumGained);
        $dominion->daily_platinum = true;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS]);

        return [
            'message' => sprintf(
                'You gain %s platinum.',
                number_format($platinumGained)
            ),
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
     * @throws GameException
     */
    public function claimLand(Dominion $dominion): array
    {
        if ($dominion->daily_land) {
            throw new GameException('You already claimed your land bonus for today.');
        }

        $landGained = 20;
        $attribute = ('land_' . $dominion->race->home_land_type);
        $dominion->increment($attribute, $landGained);
        $dominion->daily_land = true;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS]);

        return [
            'message' => sprintf(
                'You gain %d acres of %s.',
                $landGained,
                str_plural($dominion->race->home_land_type)
            ),
            'data' => [
                'landGained' => $landGained,
            ],
        ];
    }
}
