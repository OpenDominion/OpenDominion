<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\SpellHelper;

class MagicController extends AbstractDominionController
{
    public function getMagic()
    {
        return view('pages.dominion.magic', [
            'landCalculator' => app(LandCalculator::class),
            'spellHelper' => app(SpellHelper::class),
        ]);
    }

    public function postMagic(Request $request)
    {
        dd($request);
    }
}
