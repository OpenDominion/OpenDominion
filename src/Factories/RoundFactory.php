<?php

namespace OpenDominion\Factories;

use Carbon\Carbon;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Services\WonderService;

class RoundFactory
{
    // todo: move to config somewhere?
    private const ROUND_DURATION_IN_DAYS = 47;

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
     */
    public function create(
        RoundLeague $league,
        Carbon $startDate,
        int $realmSize,
        int $packSize,
        int $playersPerRace,
        bool $mixedAlignment,
        int $techVersion = TechHelper::CURRENT_VERSION
    ): Round {
        $number = ($this->getLastRoundNumber($league) + 1);
        $endDate = (clone $startDate)->addDays(static::ROUND_DURATION_IN_DAYS);

        /**
         * Random Disable - Skewed Distribution
         * Hour 10 - 30.6%
         * Hour 11 - 25%
         * Hour 12 - 19.4%
         * Hour 13 - 13.9%
         * Hour 14 - 8.3%
         * Hour 15 - 2.8%
        */
        $hoursBeforeRoundEnd = 14 - skewed_distribution(0, 6);
        $secondsBeforeRoundEnd = rand(1, 3599);
        $offensiveActionsEndDate =
            (clone $endDate)->subHours($hoursBeforeRoundEnd)->subSeconds($secondsBeforeRoundEnd);

        $round = Round::create([
            'round_league_id' => $league->id,
            'number' => $number,
            'name' => "Round {$number}",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'offensive_actions_prohibited_at' => $offensiveActionsEndDate,
            'realm_size' => $realmSize,
            'pack_size' => $packSize,
            'players_per_race' => $playersPerRace,
            'mixed_alignment' => $mixedAlignment,
            'tech_version' => $techVersion
        ]);

        // Create special realm for realm assignment and inactives
        Realm::create([
            'round_id' => $round->id,
            'alignment' => 'neutral',
            'number' => 0,
            'name' => 'The Graveyard'
        ]);

        // Spawn Starting Wonders
        $wonderService = app(WonderService::class);
        $startingWonders = $wonderService->getStartingWonders($round);
        foreach ($startingWonders as $wonder) {
            $wonderService->createWonder($round, $wonder);
        }

        return $round;
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
