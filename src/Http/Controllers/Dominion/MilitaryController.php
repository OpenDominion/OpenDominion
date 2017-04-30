<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Services\Actions\MilitaryActionService;
use OpenDominion\Services\AnalyticsService;
use Symfony\Component\HttpFoundation\Response;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        $populationCalculator = resolve(PopulationCalculator::class);

        return view('pages.dominion.military', compact(
            'populationCalculator'
        ));
    }

    public function postChangeDraftRate(/* MilitaryChangeDraftRateActionRequest */ Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $militaryActionService = resolve(MilitaryActionService::class);

        try {
            $result = $militaryActionService->changeDraftRate($dominion, $request->get('draft_rate'));

        } catch (BadInputException $e) {
            $request->session()->flash('alert-danger', 'Draft rate not changed due to bad input.');

            return redirect()->route('dominion.military')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());
        }

        $message = sprintf(
            'Draft rate changed to %d%%',
            $result['draftRate']
        );

        // todo: fire laravel event
        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
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
