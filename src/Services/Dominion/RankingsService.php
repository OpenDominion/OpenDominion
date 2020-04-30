<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

class RankingsService
{
    public function getRankingsForDominion(Dominion $dominion): array
    {
        $rankings = DB::table('daily_rankings')
            ->select('key', 'value', 'rank', 'previous_rank')
            ->where('dominion_id', $dominion->id)
            ->get()
            ->keyBy('key')
            ->map(function ($obj) {
                return (array) $obj;
            })
            ->toArray();

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
