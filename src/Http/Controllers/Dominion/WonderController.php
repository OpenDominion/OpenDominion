<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\WonderHelper;
use OpenDominion\Http\Requests\Dominion\Actions\WonderActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\Actions\WonderActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\ProtectionService;

class WonderController extends AbstractDominionController
{
    public function getWonders()
    {
        $dominion = $this->getSelectedDominion();
        $this->updateDominionWondersLastSeen($dominion);

        $wonders = $dominion->round->wonders()
            ->with(['damage', 'realm', 'wonder', 'wonder.perks'])
            ->get()
            ->sortBy('wonder.name');

        return view('pages.dominion.wonders', [
            'governmentService' => app(GovernmentService::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'wonderCalculator' => app(WonderCalculator::class),
            'wonderHelper' => app(WonderHelper::class),
            'wonders' => $wonders,
        ]);
    }

    public function postWonders(WonderActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $wonderActionService = app(WonderActionService::class);

        $action = $request->get('action');

        try {
            $roundWonder = RoundWonder::with(['realm', 'wonder'])->findOrFail($request->get('target_wonder'));
            if ($action == 'attack') {
                $result = $wonderActionService->attack(
                    $dominion,
                    $roundWonder,
                    $request->get('unit')
                );
            }
            if ($action == 'cyclone') {
                $result = $wonderActionService->castCyclone(
                    $dominion,
                    $roundWonder
                );
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()
            ->to($result['redirect'] ?? route('dominion.wonders'))
            ->with('target_wonder', $request->get('target_wonder'));
    }

    protected function updateDominionWondersLastSeen(Dominion $dominion): void
    {
        $dominion->wonders_last_seen = now();
        $dominion->save();
    }
}
