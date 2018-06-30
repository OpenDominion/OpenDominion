<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\InfoOpService;

class OpCenterController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

        // todo: filter $targetDominions by $dominion range
        // todo: keep track of how many dominions are filtered due to range? to see if upper/lower end of realm is active much

        return view('pages.dominion.op-center.index', [
            'infoOpService' => app(InfoOpService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'targetDominions' => $dominion->realm->infoOpTargetDominions,
        ]);
    }

    public function getDominion(Dominion $dominion)
    {
        $infoOpService = app(InfoOpService::class);

        if (!$infoOpService->hasInfoOps($this->getSelectedDominion()->realm, $dominion)) {
            return redirect()->route('dominion.op-center');
        }

        return view('pages.dominion.op-center.show', [
            'infoOpService' => app(InfoOpService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'dominion' => $dominion,
        ]);
    }
}
