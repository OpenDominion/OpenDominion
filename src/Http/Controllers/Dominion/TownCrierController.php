<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\GameEventService;

class TownCrierController extends AbstractDominionController
{
    public function getIndex(Request $request, int $realmNumber = null)
    {
        $gameEventService = app(GameEventService::class);
        $rangeCalculator = app(RangeCalculator::class);

        $selectedDominion = $this->getSelectedDominion();
        $realmCount = Realm::where('round_id', $selectedDominion->round_id)->count();

        $dominionId = $request->input('dominion');
        $type = $request->input('type');
        $typeChoices = ['all', 'invasions', 'wars', 'wonders', 'raids'];
        if (!in_array($type, $typeChoices)) {
            $type = 'all';
        }

        if ($realmNumber !== null) {
            $realm = Realm::where([
                'round_id' => $selectedDominion->round_id,
                'number' => $realmNumber,
            ])
            ->first();
        } else {
            $realm = null;
        }

        if ($dominionId !== null) {
            $dominion = Dominion::find($dominionId);
        } else {
            $dominion = null;
        }

        $townCrierData = $gameEventService->getTownCrier($selectedDominion, $realm, $dominion, $type);
        $gameEvents = $townCrierData['gameEvents'];
        $dominionIds = $townCrierData['dominionIds'];

        if ($dominion === null) {
            $latestEventTime = $townCrierData['gameEvents']->max('created_at');
            if ($latestEventTime !== null && $latestEventTime > $selectedDominion->town_crier_last_seen) {
                $this->updateDominionTownCrierLastSeen($selectedDominion);
            }
        }

        return view('pages.dominion.town-crier', compact(
            'dominionIds',
            'gameEvents',
            'realm',
            'realmCount',
            'rangeCalculator',
            'type',
            'typeChoices'
        ))->with([
            'fromOpCenter' => false,
            'singleDominion' => $dominion
        ]);
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
