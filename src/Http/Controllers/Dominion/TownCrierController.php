<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\GameEventService;

class TownCrierController extends AbstractDominionController
{
    public function getIndex(int $realmNumber = null)
    {
        $gameEventService = app(GameEventService::class);

        $dominion = $this->getSelectedDominion();

        if ($realmNumber !== null) {
            $realm = Realm::where([
                'round_id' => $dominion->round_id,
                'number' => $realmNumber,
            ])
            ->first();
        } else {
            $realm = null;
        }

        $townCrierData = $gameEventService->getTownCrier($dominion, $realm);

        $latestEventTime = $townCrierData['gameEvents']->max('created_at');
        if ($latestEventTime !== null && $latestEventTime > $dominion->town_crier_last_seen) {
            $this->updateDominionTownCrierLastSeen($dominion);
        }

        $gameEvents = $townCrierData['gameEvents'];
        $dominionIds = $townCrierData['dominionIds'];

        $realmCount = Realm::where('round_id', $dominion->round_id)->count();

        $rangeCalculator = app(RangeCalculator::class);
        return view('pages.dominion.town-crier', compact(
            'dominionIds',
            'gameEvents',
            'realm',
            'realmCount',
            'rangeCalculator'
        ))->with('fromOpCenter', false);
    }

    protected function updateDominionTownCrierLastSeen(Dominion $dominion): void
    {
        // Avoid using save method, which recalculates tick
        DB::table('dominions')
            ->where('id', $dominion->id)
            ->update([
                'town_crier_last_seen' => now()
            ]);
    }
}
