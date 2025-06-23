<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RaidHelper;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Traits\DominionGuardsTrait;

class RaidController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getRaids()
    {
        $raidCalculator = app(RaidCalculator::class);
        $raidHelper = app(RaidHelper::class);

        $raids = $this->getSelectedDominion()->round->raids->sortBy('order');

        return view('pages.dominion.raids', compact(
            'raidCalculator',
            'raidHelper',
            'raids'
        ));
    }

    public function getRaidObjective(RaidObjective $objective)
    {
        $raidCalculator = app(RaidCalculator::class);
        $raidHelper = app(RaidHelper::class);

        return view('pages.dominion.raid-objective', compact(
            'objective',
            'raidCalculator',
            'raidHelper',
        ));
    }
}
