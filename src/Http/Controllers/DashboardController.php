<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

class DashboardController extends AbstractController
{
    public function getIndex()
    {
        $dominions = Dominion::with(['round', 'realm', 'race'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $rounds = Round::with('league')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.dashboard', [
            'dominions' => $dominions,
            'rounds' => $rounds,
        ]);
    }
}
