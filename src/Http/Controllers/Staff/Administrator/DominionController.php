<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;

class DominionController extends AbstractController
{
    public function index()
    {
        $dominions = Dominion::with(['race.units', 'round', 'user'])->get();

        return view('pages.staff.administrator.dominions.index', [
            'dominions' => $dominions,
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
        ]);
    }

    public function show(Dominion $dominion)
    {
        return view('pages.staff.administrator.dominions.show', [
            'dominion' => $dominion,
        ]);
    }
}
