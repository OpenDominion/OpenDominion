<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\SelectorService;

class HomeController extends AbstractController
{
    public function getIndex()
    {
        // Only redirect to status/dashboard if we have no referer
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

        return view('pages.home', compact('currentRound'));
    }
}
