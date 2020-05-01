<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use OpenDominion\Helpers\RankingsHelper;

class RankingsController extends AbstractDominionController
{
    public function getRankings(Request $request, string $type = null)
    {
        $rankingsHelper = app(RankingsHelper::class);
        $rankings = $rankingsHelper->getRankings();

        if (($type === null) || !array_key_exists($type, $rankings)) {
            return redirect()->route('dominion.rankings', ['largest-dominions']);
        }

        $resultsPerPage = 10;
        $selectedDominion = $this->getSelectedDominion();

        $daily_rankings = DB::table('daily_rankings')
            ->where('round_id', $selectedDominion->round_id)
            ->where('key', $type)
            ->orderBy('rank')
            ->paginate($resultsPerPage);

        return view('pages.dominion.rankings', [
            'type' => $type,
            'rankings' => $rankings,
            'daily_rankings' => $daily_rankings,
        ]);
    }
}
