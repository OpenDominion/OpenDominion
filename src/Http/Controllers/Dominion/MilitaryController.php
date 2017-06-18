<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Services\Actions\MilitaryActionService;
use OpenDominion\Services\AnalyticsService;
use Symfony\Component\HttpFoundation\Response;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        /** @var PopulationCalculator $populationCalculator */
        $populationCalculator = app(PopulationCalculator::class);

        /** @var UnitHelper $unitHelper */
        $unitHelper = app(UnitHelper::class);

        $militaryTrainingCostPerUnit = $populationCalculator->getPopulationMilitaryTrainingCostPerUnit();
        $militaryMaxTrainable = $populationCalculator->getPopulationMilitaryMaxTrainable();

        return view('pages.dominion.military', compact(
            'populationCalculator',
            'unitHelper',
            'militaryTrainingCostPerUnit',
            'militaryMaxTrainable'
        ));
    }

    public function postChangeDraftRate(/* MilitaryChangeDraftRateActionRequest */ Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $militaryActionService = app(MilitaryActionService::class);

        try {
            $result = $militaryActionService->changeDraftRate($dominion, $request->get('draft_rate'));

        } catch (DominionLockedException $e) {
            $request->session()->flash('alert-danger', 'Draft rate not changed due to the dominion being locked.');

            return redirect()->route('dominion.military') // todo: back()
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->withInput($request->all());

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
        $analyticsService = app(AnalyticsService::class);
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
