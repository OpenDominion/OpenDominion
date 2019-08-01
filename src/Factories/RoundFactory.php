<?php

namespace OpenDominion\Factories;

use Carbon\Carbon;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundFactory
{
    // todo: move to config somewhere?
    private const ROUND_DURATION_IN_DAYS = 50;

    /**
     * Creates and returns a new Round in a RoundLeague.
     *
     * @param RoundLeague $league
     * @param Carbon $startDate
     * @param int $realmSize
     * @param int $packSize
     * @param int $playersPerRace
     * @param bool $mixedAlignment
     * @return Round
     * @throws \Exception
     */
    public function create(RoundLeague $league, Carbon $startDate, int $realmSize, int $packSize, int $playersPerRace, bool $mixedAlignment): Round
    {
        $number = ($this->getLastRoundNumber($league) + 1);
        $endDate = (clone $startDate)->addDays(static::ROUND_DURATION_IN_DAYS);

        $invasionEndHours = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 12, 13, 14, 15, 16, 12, 13, 14, 15, 16];
        shuffle($invasionEndHours);

        $hoursBeforeRoundEnd = $invasionEndHours[random_int(0, count($invasionEndHours) - 1)];

        $invasionsEndDate = (clone $endDate)->addHours(-$hoursBeforeRoundEnd);

        return Round::create([
            'round_league_id' => $league->id,
            'number' => $number,
            'name' => "Beta Round {$number}", // todo
            'start_date' => $startDate,
            'end_date' => $endDate,
            'invasions_end_date' => $invasionsEndDate,
            'realm_size' => $realmSize,
            'pack_size' => $packSize,
            'players_per_race' => $playersPerRace,
            'mixed_alignment' => $mixedAlignment
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
