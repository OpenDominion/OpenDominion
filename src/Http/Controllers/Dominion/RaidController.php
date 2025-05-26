<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RaidHelper;
use OpenDominion\Models\Raid;
use OpenDominion\Traits\DominionGuardsTrait;

class RaidController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getRaids()
    {
        $raidCalculator = app(RaidCalculator::class);
        $raidHelper = app(RaidHelper::class);

        $raids = $this->getSelectedDominion()->round->raids;

        return view('pages.dominion.raids', compact(
            'raidCalculator',
            'raidHelper',
            'raids'
        ));
    }
}
