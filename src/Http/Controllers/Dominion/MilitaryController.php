<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Services\AnalyticsService\Event;
use OpenDominion\Services\Dominion\Actions\MilitaryActionService;

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
        $militaryActionService = app(MilitaryActionService::class);

        try {
            $result = $militaryActionService->changeDraftRate($dominion, $request->get('draft_rate'));

        } catch (DominionLockedException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Draft rate not changed due to the dominion being locked.']);

        } catch (BadInputException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Draft rate not changed due to bad input.']);
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
