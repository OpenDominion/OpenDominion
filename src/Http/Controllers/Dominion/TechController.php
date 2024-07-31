<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Http\Requests\Dominion\Actions\TechActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\TechActionService;

class TechController extends AbstractDominionController
{
    public function getTechs()
    {
        return view('pages.dominion.techs', [
            'productionCalculator' => app(ProductionCalculator::class),
            'techCalculator' => app(TechCalculator::class),
            'techHelper' => app(TechHelper::class),
        ]);
    }

    public function postTechs(TechActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $techActionService = app(TechActionService::class);

        try {
            $result = $techActionService->unlock(
                $dominion,
                $request->get('key')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.techs');
    }
}
