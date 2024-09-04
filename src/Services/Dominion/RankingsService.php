<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Models\DailyRanking;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

class RankingsService
{
    public function getRankingsForDominion(Dominion $dominion): Collection
    {
        $rankings = DailyRanking::where('dominion_id', $dominion->id)
            ->get()
            ->keyBy('key');

        return $rankings;
    }

    public function getRankingsByRealm(Round $round): Collection
    {
        $rankings = DailyRanking::select('key', 'realm_number')
            ->where('round_id', $round->id)
            ->where('rank', 1)
            ->get('realm_number')
            ->mapToGroups(function ($ranking) {
                return [$ranking->realm_number => $ranking->key];
            });

        return $rankings;
    }

    public function getTopRankedDominions(Round $round): array
    {
        $rankings = DB::table('daily_rankings')
            ->select('dominion_id', 'key')
            ->where([
                'round_id' => $round->id,
                'rank' => 1,
            ])
            ->get()
            ->mapToGroups(function ($item, $key) {
                return [$item->dominion_id => $item->key];
            })
            ->toArray();

        return $rankings;
    }
}
