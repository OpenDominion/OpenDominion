<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\WonderHelper;
use OpenDominion\Http\Requests\Dominion\Actions\WonderActionRequest;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\WonderActionService;

class WonderController extends AbstractDominionController
{
    public function getWonders()
    {
        $dominion = $this->getSelectedDominion();

        return view('pages.dominion.wonders', [
            'militaryCalculator' => app(MilitaryCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'wonders' => $dominion->round->wonders()->with(['realm', 'wonder'])->get(),
            'wonderHelper' => app(WonderHelper::class),
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
            if ($action == 'spell') {
                $result = $wonderActionService->spell(
                    $dominion,
                    $roundWonder
                );
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'wonder',
            $request->get('action')
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.wonders');
    }
}
