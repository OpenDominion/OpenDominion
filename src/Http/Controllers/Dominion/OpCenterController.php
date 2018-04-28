<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Dominion;

class OpCenterController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

        $targetDominions = $dominion->realm->infoOpTargetDominions;

        // todo: filter $targetDominions by $dominion range

        // todo: keep track of how many dominions are filtered due to range? to see if upper/lower end of realm is active much

        return view('pages.dominion.op-center.index', [
            'targetDominions' => $targetDominions,
        ]);
    }

    public function getDominion(Dominion $dominion)
    {
        // assert we have info op for them
        //
    }
}
