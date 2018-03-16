<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Http\Requests\Dominion\Actions\CastSpellRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;

class MagicController extends AbstractDominionController
{
    public function getMagic()
    {
        return view('pages.dominion.magic', [
            'landCalculator' => app(LandCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
        ]);
    }

    public function postMagic(CastSpellRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $spellActionService = app(SpellActionService::class);

        try {
            $result = $spellActionService->castSelfSpell($dominion, $request->get('spell'));

        } catch (Exception $e) {
            return redirect()->back()
//                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'magic.cast.self',
            $result['data']['spell'],
            $result['data']['manaCost']
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.magic');
    }
}
