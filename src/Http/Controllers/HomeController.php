<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\SelectorService;

class HomeController extends AbstractController
{
    public function getIndex()
    {
        // Only redirect to status/dashboard if we have no referer
        // todo: this shit is still wonky. either fix or remove
        if (Auth::check() && (request()->server('HTTP_REFERER') !== '') && (url()->previous() === url()->current())) {
            $dominionSelectorService = app(SelectorService::class);

            if ($dominionSelectorService->tryAutoSelectDominionForAuthUser()) {
                return redirect()->route('dominion.status');
            }

            return redirect()->route('dashboard');
        }

        // Always assume last round is the most active one
        $currentRound = Round::query()
            ->with(['dominions', 'realms'])
            ->orderBy('created_at', 'desc')
            ->first();

        $rankingsRound = $currentRound;

        $previousRoundNumber = $currentRound->number - 1;

        if(!$currentRound->hasStarted() && $previousRoundNumber > 0)
        {
            $rankingsRound = Round::query()
            ->where('number', $previousRoundNumber)
            ->orderBy('created_at', 'desc')
            ->first();
        }

        $currentRankings = null;
        if($rankingsRound !== null)
        {
            $currentRankings = DB::table('daily_rankings')
                ->where('round_id', $rankingsRound->id)
                ->where('key', 'largest-dominions')
                ->orderBy('value', 'desc')
                ->take(10)
                ->get();
        }

        return view('pages.home', [
            'currentRound' => $currentRound,
            'currentRankings' => $currentRankings
        ]);
    }

    public function getUserAgreement()
    {
        return view('pages.user-agreement');
    }
}
