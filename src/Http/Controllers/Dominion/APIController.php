<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
/*use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\ProtectionService;*/
use Throwable;

class APIController extends AbstractDominionController
{
    public function calculateInvasion(Request $request)
    {
        $input = [
            'target' => $request->get('target_dominion'),
            'unit1' => $request->get('unit1'),
            'unit2' => $request->get('unit2'),
            'unit3' => $request->get('unit3'),
            'unit4' => $request->get('unit4')
        ];

        return $input;
    }
}
