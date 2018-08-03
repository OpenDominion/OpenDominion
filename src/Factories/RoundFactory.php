<?php

namespace OpenDominion\Factories;

use Carbon\Carbon;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundFactory
{
    // todo: move to config somewhere?
    const ROUND_DURATION_IN_DAYS = 50;

    /**
     * Creates and returns a new Round in a RoundLeague.
     *
     * @param RoundLeague $league
     * @param Carbon $startDate
     * @param int $realmSize
     * @param int $packSize
     * @return Round
     */
    public function create(RoundLeague $league, Carbon $startDate, int $realmSize, int $packSize): Round
    {
        $number = ($this->getLastRoundNumber($league) + 1);

        return Round::create([
            'round_league_id' => $league->id,
            'number' => $number,
            'name' => "Beta Round {$number}", // todo
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->addDays(static::ROUND_DURATION_IN_DAYS),
            'realm_size' => $realmSize,
            'pack_size' => $packSize,
        ]);
    }

    /**
     * Returns the last round number in a round league.
     *
     * @param RoundLeague $league
     * @return int
     */
    protected function getLastRoundNumber(RoundLeague $league): int
    {
        $round = Round::where('round_league_id', $league->id)
            ->orderBy('number', 'desc')
            ->first();

        if ($round) {
            return $round->number;
        }

        return 0;
    }
}
