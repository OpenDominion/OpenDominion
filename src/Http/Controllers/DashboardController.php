<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\SelectorService;

class DashboardController extends AbstractController
{
    public function getIndex()
    {
        $selectorService = app(SelectorService::class);
        $selectorService->tryAutoSelectDominionForAuthUser();

        $dominions = Dominion::with(['round', 'realm', 'race'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $rankings = DB::table('daily_rankings')
            ->whereIn('dominion_id', $dominions->pluck('id'))
            ->whereIn('key', ['largest-dominions', 'strongest-dominions'])
            ->get();

        $rounds = Round::with('league')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.dashboard', [
            'dominions' => $dominions,
            'rankings' => $rankings,
            'rounds' => $rounds,
        ]);
    }
}
