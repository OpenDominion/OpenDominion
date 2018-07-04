<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;

class EspionageController extends AbstractDominionController
{
    public function getEspionage()
    {
        return view('pages.dominion.espionage', [
            'espionageCalculator' => app(EspionageCalculator::class),
            'espionageHelper' => app(EspionageHelper::class),
            'landCalculator' => app(LandCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class),
        ]);
    }

    public function postEspionage(PerformEspionageRequest $request)
    {
        dd($request);
    }
}
