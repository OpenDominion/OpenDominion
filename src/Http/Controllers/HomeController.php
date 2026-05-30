<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\SelectorService;

class HomeController extends AbstractController
{
    public function getIndex(SelectorService $selectorService)
    {
        // Only redirect to status/dashboard if we have no referer
        // todo: this shit is still wonky. either fix or remove
        if (Auth::check() && (request()->server('HTTP_REFERER') !== '') && (url()->previous() === url()->current())) {
            if ($selectorService->tryAutoSelectDominionForAuthUser()) {
                return redirect()->route('dominion.status');
            }

            return redirect()->route('dashboard');
        }

        $currentRound = Round::query()
            ->with(['dominions', 'realms'])
            ->orderBy('created_at', 'desc')
            ->first();

        $rankingsRound = Round::query()
            ->where('start_date', '<=', now())
            ->orderBy('start_date', 'desc')
            ->first();

        $currentRankings = null;
        if ($rankingsRound !== null) {
            $currentRankings = DB::table('daily_rankings')
                ->where('round_id', $rankingsRound->id)
                ->where('key', 'largest-dominions')
                ->orderBy('value', 'desc')
                ->take(10)
                ->get();
        }

        if (Auth::check()) {
            if ($selectorService->hasUserSelectedDominion()) {
                $playUrl = route('dominion.status');
                $playLabel = 'Play';
            } else {
                $playUrl = route('dashboard');
                $playLabel = 'Dashboard';
            }
        } else {
            $playUrl = route('auth.register');
            $playLabel = 'Play';
        }

        $announcements = MessageBoard\Thread::forHomepage()->limit(5)->get();

        return view('pages.home', [
            'currentRound' => $currentRound,
            'currentRankings' => $currentRankings,
            'playUrl' => $playUrl,
            'playLabel' => $playLabel,
            'announcements' => $announcements,
        ]);
    }

    public function getAboutPage()
    {
        return view('pages.about');
    }

    public function getPrivacyPage()
    {
        return view('pages.privacy');
    }

    public function getTermsPage()
    {
        return view('pages.terms');
    }

    public function getUserAgreement()
    {
        return view('pages.user-agreement');
    }

    public function getHallOfFame()
    {
        return view('pages.hall-of-fame');
    }
}
