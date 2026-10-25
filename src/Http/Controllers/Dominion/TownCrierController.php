<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Http\Requests\Dominion\TownCrierPreferencesRequest;
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

        $showBotAttacks = $gameEventService->shouldShowBotAttacks($selectedDominion);
        $townCrierData = $gameEventService->getTownCrier($selectedDominion, $realm, $dominion, $type, $showBotAttacks);
        $hiddenBotAttackCount = $townCrierData['hiddenBotAttackCount'];
        $gameEvents = $townCrierData['gameEvents'];
        $dominionIds = $townCrierData['dominionIds'];

        if ($dominion === null) {
            $includesBotAttacks = $showBotAttacks && in_array($type, ['all', 'invasions'], true);
            $latestEventTime = $townCrierData['gameEvents']->max('created_at');
            $botAttacksLastSeen = $selectedDominion->settings['town_crier_bot_attacks_last_seen']
                ?? $selectedDominion->town_crier_last_seen;
            if ($latestEventTime !== null && ($latestEventTime > $selectedDominion->town_crier_last_seen
                || ($includesBotAttacks && $latestEventTime > $botAttacksLastSeen))) {
                $this->updateDominionTownCrierLastSeen($selectedDominion, $includesBotAttacks);
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
            'showBotAttacks',
            'hiddenBotAttackCount'
        ))->with([
            'fromOpCenter' => false,
            'singleDominion' => $dominion
        ]);
    }

    public function postPreferences(TownCrierPreferencesRequest $request, ?int $realmNumber = null): RedirectResponse
    {
        app(GameEventService::class)->setShowBotAttacks(
            $this->getSelectedDominion(),
            $request->boolean('show_bot_attacks')
        );

        return redirect()->route('dominion.town-crier', [
            'realmNumber' => $realmNumber,
            'type' => $request->validated('type'),
            'dominion' => $request->validated('dominion'),
        ]);
    }

    protected function updateDominionTownCrierLastSeen(Dominion $dominion, bool $includesBotAttacks = false): void
    {
        app(GameEventService::class)->markTownCrierRead($dominion, $includesBotAttacks);
    }
}
