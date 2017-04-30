<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\PopulationCalculator;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        $populationCalculator = resolve(PopulationCalculator::class);

        return view('pages.dominion.military', compact(
            'populationCalculator'
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
