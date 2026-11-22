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

        if ($request->has('dominion')) {
            $round = $selectedDominion->round;
            $targets = Dominion::where('round_id', $round->id);
            if (!$round->hasStarted()) {
                if ($round->hasAssignedRealms() && $selectedDominion->realm->number > 0) {
                    $targets->where('realm_id', $selectedDominion->realm_id);
                } else {
                    $targets->whereKey($selectedDominion->id);
                }
            }

            $dominionId = filter_var($request->input('dominion'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $dominion = $targets->whereKey($dominionId)->firstOrFail();
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
