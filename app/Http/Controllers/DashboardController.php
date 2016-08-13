<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

//use OpenDominion\Repositories\RoundRepository;

class DashboardController extends AbstractController
{
    public function getIndex()
    {
        $usersDominions = Dominion::where('user_id', Auth::user()->id)->get();
        $rounds = Round::with('league')->where('end_date', '>', new Carbon('today'))->get();

        return view('pages.dashboard', [
            'dominions' => $usersDominions,
            'rounds' => $rounds,
        ]);
    }
}
