<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Models\Dominion;

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

    public function getTopRankingsForDominion(Dominion $dominion): array
    {
        $rankings = DB::table('daily_rankings')
            ->select('key')
            ->where([
                'dominion_id' => $dominion->id,
                'rank' => 1,
            ])
            ->get()
            ->toArray();

        return $rankings;
    }

    public function getDominionTitle(Dominion $dominion): string
    {
        return '';
    }

    public function getDominionTitleIcon(Dominion $dominion): string
    {
        return '';
    }
}
