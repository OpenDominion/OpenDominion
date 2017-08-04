<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use Illuminate\Http\Request;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Services\AnalyticsService\Event;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        return view('pages.dominion.military', [
            'populationCalculator' => app(PopulationCalculator::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postChangeDraftRate(/* MilitaryChangeDraftRateActionRequest */ Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $militaryActionService = app(ChangeDraftRateActionService::class);

        try {
            $result = $militaryActionService->changeDraftRate($dominion, $request->get('draft_rate'));

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $message = sprintf(
            'Draft rate changed to %d%%.',
            $result['draftRate']
        );

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'military.change-draft-rate',
            '',
            $result['draftRate']
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.military');
    }

    public function postTrain(/* MilitaryTrainActionRequest */ Request $request)
    {
        dd($request);
    }
}
