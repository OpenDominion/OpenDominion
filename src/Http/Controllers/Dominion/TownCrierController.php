<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\GameEventService;

class TownCrierController extends AbstractDominionController
{
    public function getIndex(Request $request, ?int $realmNumber = null): View
    {
        $gameEventService = app(GameEventService::class);
        $rangeCalculator = app(RangeCalculator::class);

        $selectedDominion = $this->getSelectedDominion();
        $realmCount = Realm::where('round_id', $selectedDominion->round_id)->count();

        $dominionId = $request->input('dominion');
        $typeLabels = GameEventService::TOWN_CRIER_TYPES;
        $typeChoices = array_keys($typeLabels);
        $defaultType = $selectedDominion->settings['town_crier_default_type'] ?? GameEventService::DEFAULT_TOWN_CRIER_TYPE;
        if (!in_array($defaultType, $typeChoices, true)) {
            $defaultType = GameEventService::DEFAULT_TOWN_CRIER_TYPE;
        }
        $type = $request->input('type', $defaultType);
        if (!in_array($type, $typeChoices, true)) {
            $type = $defaultType;
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
            $latestEventTime = $townCrierData['latestEventTime'];
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
            'typeChoices',
            'typeLabels',
            'defaultType'
        ))->with([
            'fromOpCenter' => false,
            'singleDominion' => $dominion
        ]);
    }

    protected function updateDominionTownCrierLastSeen(Dominion $dominion): void
    {
        // Avoid save(), which recalculates networth and next-tick projections.
        Dominion::query()->whereKey($dominion->id)->toBase()->update(['town_crier_last_seen' => now()]);
    }
}
