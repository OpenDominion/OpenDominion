<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Services\GameEventService;

class TownCrierController extends AbstractDominionController
{
    public function getIndex()
    {
        $gameEventService = app(GameEventService::class);

        $dominion = $this->getSelectedDominion();
        $realm = $dominion->realm;

        $townCrierData = $gameEventService->getTownCrier($dominion);

        $gameEvents = $townCrierData['gameEvents'];
        $dominionIds = $townCrierData['dominionIds'];

        return view('pages.dominion.town-crier', compact(
            'gameEvents',
            'realm',
            'dominionIds'
        ))->with('fromOpCenter', false);
    }
}
