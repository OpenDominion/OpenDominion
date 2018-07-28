<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\InvadeActionRequest;
use OpenDominion\Services\Dominion\ProtectionService;

class InvasionController extends AbstractDominionController
{
    public function getInvade()
    {
        return view('pages.dominion.invade',  [
            'landCalculator' => app(LandCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postInvade(InvadeActionRequest $request)
    {
        dd($request);
    }
}
