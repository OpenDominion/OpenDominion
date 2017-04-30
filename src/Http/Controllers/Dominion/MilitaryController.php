<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        return view('pages.dominion.military', compact(
            null
        ));
    }

    public function postSetDraftRate(Request $request)
    {
        dd($request);
    }

    public function postTrain(Request $request)
    {
        dd($request);
    }
}
